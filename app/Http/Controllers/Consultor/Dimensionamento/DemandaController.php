<?php

namespace App\Http\Controllers\Consultor\Dimensionamento;

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
use App\Services\PrecificacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DemandaController extends Controller
{
    public function __construct(
        private DimensionamentoService $dimensionamento,
        private PrecificacaoService $precificacao,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Consultor/Orcamentos/Create', [
            'tipo' => 'demanda',
            'estruturas' => Estrutura::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'clientes' => Cliente::where('consultor_id', Auth::id())
                ->with('cidade:id,cidade,estado')
                ->orderBy('nome')
                ->get(['id', 'tipo_pessoa', 'nome', 'razao_social', 'cidade_id']),
            'concessionarias' => Concessionaria::where('ativo', true)
                ->whereNotNull('tarifa_ponta')
                ->whereNotNull('tarifa_fora_ponta')
                ->orderBy('estado')->orderBy('nome')
                ->get(['id', 'nome', 'estado', 'tarifa_ponta', 'tarifa_fora_ponta']),
        ]);
    }

    public function buscarKits(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('consultor_id', $request->user()->id)],
            'estrutura_id' => 'required|exists:estruturas,id',
            'tensao' => 'required|integer|in:127,220,380',
            'qtd_kits' => 'required|integer|min:1|max:10',
            'consumo_ponta' => 'required|numeric|min:1',
            'consumo_fora_ponta' => 'required|numeric|min:1',
            'concessionaria_id' => 'required|exists:concessionarias,id',
            'orientacao' => 'required|in:norte,nordeste_noroeste,leste_oeste,sudeste_sudoeste,sul',
            'categorias' => 'nullable|array',
            'categorias.*' => 'in:ongrid,offgrid,hibrido,bomba,microinversor',
        ]);

        $cliente = Cliente::with('cidade')->findOrFail($data['cliente_id']);
        $concessionaria = Concessionaria::findOrFail($data['concessionaria_id']);

        if (! $cliente->cidade_id) {
            return response()->json(['error' => 'Cliente sem cidade cadastrada. Edite o cliente e informe a cidade antes de dimensionar.'], 422);
        }

        $params = $this->dimensionamento->getParams();
        $hsp = $this->dimensionamento->getIrradiacao($cliente->cidade_id);
        $orientacao = $data['orientacao'];

        $potencia = $this->dimensionamento->calcularPotenciaDemanda(
            (float) $data['consumo_ponta'],
            (float) $data['consumo_fora_ponta'],
            (float) $concessionaria->tarifa_ponta,
            (float) $concessionaria->tarifa_fora_ponta,
            $hsp,
            $params,
            $orientacao,
        );

        $categorias = $data['categorias'] ?? [];
        $kits = $this->dimensionamento->buscarKits($potencia, (int) $data['estrutura_id'], (int) $data['tensao'], (int) $data['qtd_kits'], $categorias);
        $estado = $cliente->cidade->sigla ?? $cliente->cidade->estado;
        $qtd = (int) $data['qtd_kits'];
        $pr = $this->dimensionamento->detalhamentoPR($params, $orientacao);

        $consumoTotal = (float) $data['consumo_ponta'] + (float) $data['consumo_fora_ponta'];

        $kitsResult = $kits->map(function (Kit $kit) use ($qtd, $estado, $hsp, $params, $orientacao) {
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

        $analiseMensal = null;
        if ($kitsResult->isNotEmpty()) {
            $kitRef = $kits->first();
            $potRef = (float) $kitRef->potencia_kwp * $qtd;
            $analiseMensal = $this->dimensionamento->analiseMensal(
                $cliente->cidade_id,
                $potRef,
                $consumoTotal,
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
            'consumo_ponta' => 'required|numeric|min:1',
            'consumo_fora_ponta' => 'required|numeric|min:1',
            'concessionaria_id' => 'required|exists:concessionarias,id',
            'kit_id' => 'required|exists:kits,id',
            'grupo_tarifario' => 'required|in:A4,A3a,A3,A2,A1',
            'anotacoes' => 'nullable|string|max:2000',
            'anotacoes_tecnicas' => 'nullable|string|max:2000',
        ]);

        $cliente = Cliente::with('cidade')->findOrFail($data['cliente_id']);
        $concessionaria = Concessionaria::findOrFail($data['concessionaria_id']);
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

        DB::transaction(function () use ($data, $cliente, $concessionaria, $user, $kit, $qtd, $preco, $hsp, $orientacao, $pr, &$orcamentoId) {
            $orcamento = Orcamento::create([
                'consultor_id' => $user->id,
                'cliente_id' => $cliente->id,
                'cidade_id' => $cliente->cidade_id,
                'status' => 'novo',
                'grupo_tarifario' => $data['grupo_tarifario'],
                'preco_total' => $preco['preco_venda'],
                'geracao_estimada' => (int) $data['geracao_estimada'],
                'anotacoes' => $data['anotacoes'] ?? null,
            ]);

            OrcamentoInfo::create([
                'orcamento_id' => $orcamento->id,
                'estrutura_id' => $data['estrutura_id'],
                'tipo_dimensionamento' => 'demanda',
                'consumo' => (float) $data['consumo_fora_ponta'] + (float) $data['consumo_ponta'],
                'tensao' => $data['tensao'],
                'orientacao' => $orientacao,
                'anotacoes_tecnicas' => $data['anotacoes_tecnicas'] ?? null,
                'metadados' => [
                    'consumo_ponta' => $data['consumo_ponta'],
                    'consumo_fora_ponta' => $data['consumo_fora_ponta'],
                    'concessionaria_id' => $concessionaria->id,
                    'concessionaria' => $concessionaria->nome,
                    'tarifa_ponta' => $concessionaria->tarifa_ponta,
                    'tarifa_fora_ponta' => $concessionaria->tarifa_fora_ponta,
                    'fc' => round((float) $concessionaria->tarifa_ponta / max((float) $concessionaria->tarifa_fora_ponta, 0.0001), 3),
                    'pr' => $pr,
                    'hsp' => $hsp,
                ],
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
