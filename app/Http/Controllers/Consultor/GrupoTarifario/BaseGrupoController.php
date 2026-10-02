<?php

namespace App\Http\Controllers\Consultor\GrupoTarifario;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Concessionaria;
use App\Models\Estrutura;
use App\Models\Kit;
use App\Models\Orcamento;
use App\Models\OrcamentoHistorico;
use App\Models\OrcamentoInfo;
use App\Models\OrcamentoItem;
use App\Services\DimensionamentoService;
use App\Services\GrupoTarifarioService;
use App\Services\PrecificacaoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Base compartilhada por todos os controllers de grupo tarifário.
 * Contém o fluxo comum de busca de kits e persistência do orçamento.
 */
abstract class BaseGrupoController extends Controller
{
    public function __construct(
        protected DimensionamentoService $dimensionamento,
        protected PrecificacaoService $precificacao,
        protected GrupoTarifarioService $grupoService,
    ) {}

    // ── Dados comuns para as views ────────────────────────────────────────

    protected function dadosComuns(): array
    {
        $consultorId = Auth::id();

        return [
            'estruturas' => Estrutura::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'clientes' => Cliente::where('consultor_id', $consultorId)
                ->with('cidade:id,cidade,estado,sigla')
                ->orderBy('nome')
                ->get(['id', 'tipo_pessoa', 'nome', 'razao_social', 'cidade_id']),
            'concessionarias' => Concessionaria::where('ativo', true)
                ->orderBy('estado')->orderBy('nome')
                ->get(['id', 'nome', 'estado',
                    'tarifa_convencional', 'tarifa_ponta', 'tarifa_fora_ponta', 'tarifa_intermediaria']),
        ];
    }

    // ── Busca e mapeamento de kits (fluxo comum) ──────────────────────────

    protected function mapearKits(
        \Illuminate\Database\Eloquent\Collection $kits,
        int $qtd,
        float $hsp,
        array $params,
        string $orientacao,
        string $estado,
    ): Collection {
        return $kits->map(function (Kit $kit) use ($qtd, $hsp, $params, $orientacao, $estado) {
            $potenciaTotal = (float) $kit->potencia_kwp * $qtd;
            $preco = $this->precificacao->calcular($kit, $qtd, $estado);
            $geracao = $this->dimensionamento->calcularGeracao($hsp, $potenciaTotal, $params, $orientacao);

            return [
                'id' => $kit->id,
                'nome' => $kit->nome,
                'modelo' => $kit->modelo,
                'categoria' => $kit->categoria,
                'potencia_kwp' => round($potenciaTotal, 3),
                'fornecedor' => $kit->fornecedor?->nome,
                'geracao' => $geracao,
                'preco_custo' => $preco['preco_custo'],
                'preco_venda' => $preco['preco_venda'],
                'margem_total' => $preco['margem_total'],
            ];
        })->values();
    }

    // ── Persistência do orçamento ─────────────────────────────────────────

    /**
     * Preço e geração do kit escolhido, sempre calculados no servidor —
     * nunca confiar em geracao_estimada/preco_venda enviados pelo navegador.
     *
     * @return array{cliente: Cliente, kit: Kit, qtd: int, preco: array, hsp: float, orientacao: string, pr: array, geracao: int}
     */
    protected function kitSelecionado(array $dados): array
    {
        $cliente = Cliente::with('cidade')->findOrFail($dados['cliente_id']);
        $kit = Kit::findOrFail($dados['kit_id']);
        $qtd = (int) $dados['qtd_kits'];
        $estado = $cliente->cidade?->sigla ?? $cliente->cidade?->estado ?? '';
        $params = $this->dimensionamento->getParams();
        $hsp = $this->dimensionamento->getIrradiacao($cliente->cidade_id);
        $orientacao = $dados['orientacao'];

        return [
            'cliente' => $cliente,
            'kit' => $kit,
            'qtd' => $qtd,
            'preco' => $this->precificacao->calcular($kit, $qtd, $estado),
            'hsp' => $hsp,
            'orientacao' => $orientacao,
            'pr' => $this->dimensionamento->detalhamentoPR($params, $orientacao),
            'geracao' => $this->dimensionamento->calcularGeracao($hsp, (float) $kit->potencia_kwp * $qtd, $params, $orientacao),
        ];
    }

    protected function salvarOrcamento(array $dados, array $infoExtra, array $analiseEconomica): int
    {
        ['cliente' => $cliente, 'kit' => $kit, 'qtd' => $qtd, 'preco' => $preco, 'hsp' => $hsp,
            'orientacao' => $orientacao, 'pr' => $pr, 'geracao' => $geracao] = $this->kitSelecionado($dados);
        $user = Auth::user();

        $orcamentoId = null;

        DB::transaction(function () use (
            $dados, $infoExtra, $analiseEconomica,
            $cliente, $kit, $user, $qtd, $preco, $hsp, $orientacao, $pr, $geracao,
            &$orcamentoId
        ) {
            $orcamento = Orcamento::create([
                'consultor_id' => $user->id,
                'cliente_id' => $cliente->id,
                'cidade_id' => $cliente->cidade_id,
                'status' => 'novo',
                'grupo_tarifario' => $dados['grupo_tarifario'],
                'modalidade_tarifaria' => $dados['modalidade_tarifaria'] ?? 'convencional',
                'preco_total' => $preco['preco_venda'],
                'geracao_estimada' => $geracao,
                'anotacoes' => $dados['anotacoes'] ?? null,
            ]);

            OrcamentoInfo::create(array_merge([
                'orcamento_id' => $orcamento->id,
                'estrutura_id' => $dados['estrutura_id'],
                'tipo_dimensionamento' => 'convencional',
                'consumo' => $dados['consumo'] ?? null,
                'tensao' => $dados['tensao'],
                'orientacao' => $orientacao,
                'anotacoes_tecnicas' => $dados['anotacoes_tecnicas'] ?? null,
                'analise_economica' => $analiseEconomica,
                'metadados' => ['pr' => $pr, 'hsp' => $hsp],
            ], $infoExtra));

            OrcamentoItem::create([
                'orcamento_id' => $orcamento->id,
                'tipo' => 'kit',
                'kit_id' => $kit->id,
                'descricao' => $kit->nome,
                'quantidade' => $qtd,
                'preco_custo_unitario' => $kit->preco_custo,
                'preco_venda_unitario' => $qtd > 0 ? round($preco['preco_venda'] / $qtd, 2) : $preco['preco_venda'],
                'preco_venda_total' => $preco['preco_venda'],
                'margem_percentual' => $preco['margem_total'],
                'comissao_percentual' => $user->comissao_percentual,
                'geracao_estimada' => $geracao,
                'metadados' => ['potencia_kwp' => round((float) $kit->potencia_kwp * $qtd, 3), 'hsp' => $hsp, 'orientacao' => $orientacao],
                'ordem' => 1,
            ]);

            OrcamentoHistorico::create([
                'orcamento_id' => $orcamento->id,
                'usuario_id' => $user->id,
                'status' => 'novo',
                'mensagem' => "Orçamento {$dados['grupo_tarifario']} criado.",
            ]);

            $orcamentoId = $orcamento->id;
        });

        return $orcamentoId;
    }
}
