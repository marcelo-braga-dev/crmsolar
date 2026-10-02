<?php

namespace App\Http\Controllers\Consultor\GrupoTarifario;

use App\Models\Cliente;
use App\Services\GrupoTarifarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Grupo B3 — Comercial / Industrial Pequeno Porte (Baixa Tensão)
 *
 * Tarifa convencional para consumidores comerciais em BT.
 * A análise de autoconsumo é relevante: quanto da geração é consumida
 * no próprio estabelecimento durante o horário de funcionamento
 * (vs. injetada na rede para crédito).
 */
class GrupoB3Controller extends BaseGrupoController
{
    public function create(): Response
    {
        return Inertia::render('Consultor/Orcamentos/GrupoB3', $this->dadosComuns());
    }

    public function calcular(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('consultor_id', $request->user()->id)],
            'estrutura_id' => 'required|exists:estruturas,id',
            'tensao' => 'required|integer|in:127,220,380',
            'qtd_kits' => 'required|integer|min:1|max:20',
            'orientacao' => 'required|in:norte,nordeste_noroeste,leste_oeste,sudeste_sudoeste,sul',
            'fases' => 'required|in:bifasico,trifasico',
            'consumo' => 'required|numeric|min:1',
            'tarifa_kwh' => 'required|numeric|min:0.01',
            'valor_conta_mensal' => 'nullable|numeric|min:0',
            'objetivo_percentual' => 'required|integer|in:50,75,100',
            'horario_funcionamento' => 'required|integer|min:4|max:24',
            'percentual_autoconsumo' => 'required|integer|min:10|max:100',
            'categorias' => 'nullable|array',
            'categorias.*' => 'in:ongrid,offgrid,hibrido,bomba,microinversor',
        ]);

        $cliente = Cliente::with('cidade')->findOrFail($data['cliente_id']);
        if (! $cliente->cidade_id) {
            return response()->json(['error' => 'Cliente sem cidade cadastrada.'], 422);
        }

        $params = $this->dimensionamento->getParams();
        $hsp = $this->dimensionamento->getIrradiacao($cliente->cidade_id);
        $orientacao = $data['orientacao'];
        $consumo = (float) $data['consumo'];
        $tarifa = (float) $data['tarifa_kwh'];
        $autoconsumo = (int) $data['percentual_autoconsumo'];

        // Dimensionamento: comercial considera que só o que é autoconsumido gera economia direta
        // O restante vira crédito de energia (menos eficiente economicamente no curto prazo)
        $consumoDimensionar = $consumo * ($data['objetivo_percentual'] / 100);
        $potencia = $this->dimensionamento->calcularPotencia($consumoDimensionar, $hsp, $params, $orientacao);

        $categorias = $data['categorias'] ?? [];
        $kits = $this->dimensionamento->buscarKits($potencia, (int) $data['estrutura_id'], (int) $data['tensao'], (int) $data['qtd_kits'], $categorias);
        $estado = $cliente->cidade->sigla ?? $cliente->cidade->estado;
        $qtd = (int) $data['qtd_kits'];
        $pr = $this->dimensionamento->detalhamentoPR($params, $orientacao);

        if ($kits->isEmpty()) {
            return response()->json(['error' => 'Nenhum kit encontrado. Ajuste a estrutura, tensão ou consumo.'], 404);
        }

        $kitsResult = $this->mapearKits($kits, $qtd, $hsp, $params, $orientacao, $estado);

        $kitsResult = $kitsResult->map(function ($kit) use ($consumo, $tarifa, $data, $autoconsumo) {
            // B3: economia considera percentual de autoconsumo
            $geracaoAutoconsumo = $kit['geracao'] * ($autoconsumo / 100);
            $geracaoInjetada = $kit['geracao'] - $geracaoAutoconsumo;

            // Autoconsumo economiza ao preço pleno da tarifa
            // Injeção economiza ao preço da tarifa (compensação 1:1 no Grupo B)
            $totalGeracaoEfetiva = min($kit['geracao'], $consumo);
            $economia = $this->grupoService->economiaGrupoB(
                $totalGeracaoEfetiva, $consumo, $tarifa, $data['fases'], (int) $data['objetivo_percentual'],
            );
            $analise = $this->grupoService->analiseCompleta($kit['preco_venda'], $economia['economia_mensal']);

            return array_merge($kit, [
                'geracao_autoconsumo' => round($geracaoAutoconsumo, 0),
                'geracao_injetada' => round($geracaoInjetada, 0),
                'economia' => $economia,
                'payback_simples' => $analise['payback_simples'],
                'payback_descontado' => $analise['payback_descontado'],
                'vpl_25a' => $analise['vpl_25a'],
                'tir_25a' => $analise['tir_25a'],
                'economia_total_25a' => $analise['economia_total_25a'],
                'roi_percentual' => $analise['roi_percentual'],
            ]);
        });

        $analiseMensal = $this->dimensionamento->analiseMensal(
            $cliente->cidade_id,
            (float) $kits->first()->potencia_kwp * $qtd,
            $consumo, $params, $orientacao,
        );

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
            'qtd_kits' => 'required|integer|min:1|max:20',
            'orientacao' => 'required|in:norte,nordeste_noroeste,leste_oeste,sudeste_sudoeste,sul',
            'fases' => 'required|in:bifasico,trifasico',
            'consumo' => 'required|numeric|min:1',
            'tarifa_kwh' => 'required|numeric|min:0.01',
            'valor_conta_mensal' => 'nullable|numeric|min:0',
            'objetivo_percentual' => 'required|integer|in:50,75,100',
            'horario_funcionamento' => 'required|integer|min:4|max:24',
            'percentual_autoconsumo' => 'required|integer|min:10|max:100',
            'kit_id' => 'required|exists:kits,id',
            'anotacoes' => 'nullable|string|max:3000',
            'anotacoes_tecnicas' => 'nullable|string|max:3000',
        ]);

        $selecao = $this->kitSelecionado($data);
        $geracao = $selecao['geracao'];
        $economia = $this->grupoService->economiaGrupoB($geracao, (float) $data['consumo'], (float) $data['tarifa_kwh'], $data['fases'], (int) $data['objetivo_percentual']);
        $analise = $this->grupoService->analiseCompleta($selecao['preco']['preco_venda'], $economia['economia_mensal']);

        $infoExtra = [
            'tarifa_kwh' => $data['tarifa_kwh'],
            'valor_conta_mensal' => $data['valor_conta_mensal'] ?? null,
            'fases' => $data['fases'],
            'disponibilidade_kwh' => GrupoTarifarioService::disponibilidadePorFase($data['fases']),
            'objetivo_percentual' => (int) $data['objetivo_percentual'],
            'horario_funcionamento' => (int) $data['horario_funcionamento'],
            'percentual_autoconsumo' => (int) $data['percentual_autoconsumo'],
        ];

        $id = $this->salvarOrcamento(
            array_merge($data, ['grupo_tarifario' => 'B3', 'modalidade_tarifaria' => 'convencional']),
            $infoExtra,
            $analise,
        );

        return redirect()->route('consultor.orcamentos.show', $id)
            ->with('success', 'Orçamento B3 Comercial criado com sucesso!');
    }
}
