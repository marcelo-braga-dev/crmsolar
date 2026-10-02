<?php

namespace App\Http\Controllers\Consultor\Dimensionamento;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Estrutura;
use App\Models\Kit;
use App\Models\Orcamento;
use App\Models\OrcamentoHistorico;
use App\Models\OrcamentoInfo;
use App\Models\OrcamentoItem;
use App\Services\DimensionamentoService;
use App\Services\PrecificacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Inertia\Response;

class ConvencionalController extends Controller
{
    public function __construct(
        private DimensionamentoService $dimensionamento,
        private PrecificacaoService $precificacao,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Consultor/Orcamentos/Create', [
            'tipo' => 'convencional',
            'estruturas' => Estrutura::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'clientes' => Cliente::where('consultor_id', Auth::id())
                ->with('cidade:id,cidade,estado,sigla')
                ->orderBy('nome')
                ->get(['id', 'tipo_pessoa', 'nome', 'razao_social', 'cidade_id']),
        ]);
    }

    public function buscarKits(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('consultor_id', $request->user()->id)],
            'estrutura_id' => 'required|exists:estruturas,id',
            'tensao' => 'required|integer|in:127,220,380',
            'qtd_kits' => 'required|integer|min:1|max:10',
            'consumo' => 'required|numeric|min:0.1',
            'orientacao' => 'required|in:norte,nordeste_noroeste,leste_oeste,sudeste_sudoeste,sul',
            'kwp_direto' => 'nullable|numeric|min:0.1',
            'categorias' => 'nullable|array',
            'categorias.*' => 'in:ongrid,offgrid,hibrido,bomba,microinversor',
        ]);

        $cliente = Cliente::with('cidade')->findOrFail($data['cliente_id']);

        if (! $cliente->cidade_id) {
            return response()->json(['error' => 'Cliente sem cidade cadastrada. Edite o cliente e informe a cidade antes de dimensionar.'], 422);
        }

        $params = $this->dimensionamento->getParams();
        $hsp = $this->dimensionamento->getIrradiacao($cliente->cidade_id);
        $orientacao = $data['orientacao'];

        // Modo kWp direto: usa a potência informada diretamente, sem calcular pelo consumo
        if (! empty($data['kwp_direto'])) {
            $potencia = (float) $data['kwp_direto'];
        } else {
            $potencia = $this->dimensionamento->calcularPotencia(
                (float) $data['consumo'],
                $hsp,
                $params,
                $orientacao,
            );
        }

        $categorias = $data['categorias'] ?? [];
        $kits = $this->dimensionamento->buscarKits($potencia, (int) $data['estrutura_id'], (int) $data['tensao'], (int) $data['qtd_kits'], $categorias);
        $estado = $cliente->cidade->sigla ?? $cliente->cidade->estado;
        $qtd = (int) $data['qtd_kits'];
        $pr = $this->dimensionamento->detalhamentoPR($params, $orientacao);

        $kitsResult = $kits->map(function (Kit $kit) use ($qtd, $estado, $data, $hsp, $params, $orientacao) {
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

        // Análise mensal com o kit mais barato (se houver resultado)
        $analiseMensal = null;
        if ($kitsResult->isNotEmpty()) {
            $kitRef = $kits->first();
            $potRef = (float) $kitRef->potencia_kwp * $qtd;
            $analiseMensal = $this->dimensionamento->analiseMensal(
                $cliente->cidade_id,
                $potRef,
                (float) $data['consumo'],
                $params,
                $orientacao,
            );
        }

        return response()->json([
            'potencia_calculada' => $potencia,
            'hsp' => $hsp,
            'pr' => $pr,
            'kits' => $kitsResult,
            'analise_mensal' => $analiseMensal,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('consultor_id', $request->user()->id)],
            'estrutura_id' => 'required|exists:estruturas,id',
            'tensao' => 'required|integer|in:127,220,380',
            'orientacao' => 'required|in:norte,nordeste_noroeste,leste_oeste,sudeste_sudoeste,sul',
            'qtd_kits' => 'required|integer|min:1|max:10',
            'consumo' => 'required|numeric|min:1',
            'kit_id' => 'required|exists:kits,id',
            'anotacoes' => 'nullable|string|max:2000',
            'anotacoes_tecnicas' => 'nullable|string|max:2000',
        ]);

        $cliente = Cliente::with('cidade')->findOrFail($data['cliente_id']);
        $user = Auth::user();
        $kit = Kit::findOrFail($data['kit_id']);
        $params = $this->dimensionamento->getParams();
        $hsp = $this->dimensionamento->getIrradiacao($cliente->cidade_id);
        $orientacao = $data['orientacao'];
        $estado = $cliente->cidade?->sigla ?? $cliente->cidade?->estado ?? '';
        $qtd = (int) $data['qtd_kits'];
        $preco = $this->precificacao->calcular($kit, $qtd, $estado);
        $pr = $this->dimensionamento->detalhamentoPR($params, $orientacao);
        // Geração sempre calculada no servidor — o valor enviado pelo navegador é ignorado.
        $data['geracao_estimada'] = $this->dimensionamento->calcularGeracao($hsp, (float) $kit->potencia_kwp * $qtd, $params, $orientacao);

        $orcamentoId = null;

        DB::transaction(function () use ($data, $cliente, $user, $kit, $qtd, $preco, $hsp, $orientacao, $pr, &$orcamentoId) {
            $orcamento = Orcamento::create([
                'consultor_id' => $user->id,
                'cliente_id' => $cliente->id,
                'cidade_id' => $cliente->cidade_id,
                'status' => 'novo',
                'preco_total' => $preco['preco_venda'],
                'geracao_estimada' => (int) $data['geracao_estimada'],
                'anotacoes' => $data['anotacoes'] ?? null,
            ]);

            OrcamentoInfo::create([
                'orcamento_id' => $orcamento->id,
                'estrutura_id' => $data['estrutura_id'],
                'tipo_dimensionamento' => 'convencional',
                'consumo' => $data['consumo'],
                'tensao' => $data['tensao'],
                'orientacao' => $orientacao,
                'anotacoes_tecnicas' => $data['anotacoes_tecnicas'] ?? null,
                'metadados' => ['pr' => $pr, 'hsp' => $hsp],
            ]);

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
                'geracao_estimada' => (int) $data['geracao_estimada'],
                'metadados' => [
                    'potencia_kwp' => round((float) $kit->potencia_kwp * $qtd, 3),
                    'consumo' => $data['consumo'],
                    'hsp' => $hsp,
                    'pr_total' => $pr['pr_total'],
                    'orientacao' => $orientacao,
                ],
                'ordem' => 1,
            ]);

            OrcamentoHistorico::create([
                'orcamento_id' => $orcamento->id,
                'usuario_id' => $user->id,
                'status' => 'novo',
                'mensagem' => 'Orçamento criado.',
            ]);

            $orcamentoId = $orcamento->id;
        });

        return redirect()
            ->route('consultor.orcamentos.show', $orcamentoId)
            ->with('success', 'Orçamento criado com sucesso!');
    }
}
