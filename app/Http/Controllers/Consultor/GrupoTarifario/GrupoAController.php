<?php

namespace App\Http\Controllers\Consultor\GrupoTarifario;

use App\Http\Requests\Consultor\Dimensionamento\GrupoARequest;
use App\Models\Cliente;
use App\Services\GrupoTarifarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Grupo A — Média e Alta Tensão (A4, A3a, A3, A2, A1)
 *
 * Modalidades tarifárias:
 *   THS Verde: demanda única (R$/kW) + consumo diferenciado ponta/fora-ponta
 *   THS Azul:  demanda diferenciada ponta/fora-ponta + consumo diferenciado ponta/fora-ponta
 *
 * A análise de viabilidade no Grupo A inclui:
 *   - Redução de consumo ponta e fora-ponta
 *   - Possível redução de demanda de ponta (limitada e conservadora)
 *   - Cálculo de payback, VPL e TIR
 */
class GrupoAController extends BaseGrupoController
{
    public function create(string $grupo): Response
    {
        $grupos = GrupoTarifarioService::grupos();
        abort_unless(array_key_exists($grupo, $grupos) && $grupos[$grupo]['tensao'] !== 'BT', 404);

        return Inertia::render('Consultor/Orcamentos/GrupoA', array_merge(
            $this->dadosComuns(),
            ['grupo' => $grupo, 'grupo_info' => $grupos[$grupo]],
        ));
    }

    public function calcular(GrupoARequest $request): JsonResponse
    {
        $data = $request->validated();

        $cliente = Cliente::with('cidade')->findOrFail($data['cliente_id']);
        if (! $cliente->cidade_id) {
            return response()->json(['error' => 'Cliente sem cidade cadastrada.'], 422);
        }

        $params = $this->dimensionamento->getParams();
        $hsp = $this->dimensionamento->getIrradiacao($cliente->cidade_id);
        $orientacao = $data['orientacao'];

        // Dimensionamento por demanda (usa fator de custo entre tarifas)
        $potencia = $this->dimensionamento->calcularPotenciaDemanda(
            (float) $data['consumo_ponta'],
            (float) $data['consumo_fora_ponta'],
            (float) $data['tarifa_kwh_ponta'],
            (float) $data['tarifa_kwh_fp'],
            $hsp, $params, $orientacao,
        );

        $categorias = $data['categorias'] ?? [];
        $kits = $this->dimensionamento->buscarKits($potencia, (int) $data['estrutura_id'], (int) $data['tensao'], (int) $data['qtd_kits'], $categorias);
        $estado = $cliente->cidade->sigla ?? $cliente->cidade->estado;
        $qtd = (int) $data['qtd_kits'];
        $pr = $this->dimensionamento->detalhamentoPR($params, $orientacao);
        $autoconsumo = (int) $data['percentual_autoconsumo'];

        if ($kits->isEmpty()) {
            return response()->json(['error' => 'Nenhum kit encontrado. Ajuste estrutura, tensão ou consumo.'], 404);
        }

        $kitsResult = $this->mapearKits($kits, $qtd, $hsp, $params, $orientacao, $estado);

        $kitsResult = $kitsResult->map(function ($kit) use ($data, $autoconsumo) {
            $economia = $this->grupoService->economiaGrupoA(
                $kit['geracao'],
                (float) $data['consumo_ponta'],
                (float) $data['consumo_fora_ponta'],
                (float) $data['tarifa_kwh_ponta'],
                (float) $data['tarifa_kwh_fp'],
                $autoconsumo,
                $data['modalidade_tarifaria'],
            );

            // Redução de demanda (conservador)
            $reducaoDemanda = $this->grupoService->reducaoDemanda(
                $kit['potencia_kwp'],
                (float) $data['demanda_ponta_kw'],
            );

            // Economia de demanda de ponta
            $economiaDemanda = $reducaoDemanda['reducao_kw'] * (float) $data['tarifa_demanda_ponta'];
            $economiaMensalTotal = $economia['economia_mensal'] + $economiaDemanda;

            $analise = $this->grupoService->analiseCompleta($kit['preco_venda'], $economiaMensalTotal);

            return array_merge($kit, [
                'economia' => $economia,
                'reducao_demanda' => $reducaoDemanda,
                'economia_demanda_mes' => round($economiaDemanda, 2),
                'economia_total_mes' => round($economiaMensalTotal, 2),
                'payback_simples' => $analise['payback_simples'],
                'payback_descontado' => $analise['payback_descontado'],
                'vpl_25a' => $analise['vpl_25a'],
                'tir_25a' => $analise['tir_25a'],
                'economia_total_25a' => $analise['economia_total_25a'],
                'roi_percentual' => $analise['roi_percentual'],
            ]);
        });

        $consumoTotal = (float) $data['consumo_ponta'] + (float) $data['consumo_fora_ponta'];
        $analiseMensal = $this->dimensionamento->analiseMensal(
            $cliente->cidade_id,
            (float) $kits->first()->potencia_kwp * $qtd,
            $consumoTotal, $params, $orientacao,
        );

        return response()->json([
            'potencia_calculada' => $potencia,
            'hsp' => $hsp,
            'pr' => $pr,
            'kits' => $kitsResult,
            'analise_mensal' => $analiseMensal,
        ]);
    }

    public function store(GrupoARequest $request): RedirectResponse
    {
        $data = $request->validated();

        $selecao = $this->kitSelecionado($data);
        $geracao = $selecao['geracao'];
        $economia = $this->grupoService->economiaGrupoA(
            $geracao,
            (float) $data['consumo_ponta'],
            (float) $data['consumo_fora_ponta'],
            (float) $data['tarifa_kwh_ponta'],
            (float) $data['tarifa_kwh_fp'],
            (int) $data['percentual_autoconsumo'],
            $data['modalidade_tarifaria'],
        );
        $analise = $this->grupoService->analiseCompleta($selecao['preco']['preco_venda'], $economia['economia_mensal']);

        $infoExtra = [
            'tipo_dimensionamento' => 'demanda',
            'consumo_ponta' => $data['consumo_ponta'],
            'consumo_fora_ponta' => $data['consumo_fora_ponta'],
            'demanda_contratada' => $data['demanda_ponta_kw'],
            'demanda_ponta_kw' => $data['demanda_ponta_kw'],
            'demanda_fora_ponta_kw' => $data['demanda_fora_ponta_kw'] ?? null,
            'tarifa_kwh_ponta' => $data['tarifa_kwh_ponta'],
            'tarifa_kwh_fp' => $data['tarifa_kwh_fp'],
            'tarifa_demanda_ponta' => $data['tarifa_demanda_ponta'],
            'tarifa_demanda_fp' => $data['tarifa_demanda_fp'] ?? null,
            'valor_conta_mensal' => $data['valor_conta_mensal'] ?? null,
            'percentual_autoconsumo' => (int) $data['percentual_autoconsumo'],
            'subgrupo_tensao' => $data['subgrupo_tensao'] ?? null,
        ];

        $id = $this->salvarOrcamento(
            array_merge($data, [
                'consumo' => (float) $data['consumo_ponta'] + (float) $data['consumo_fora_ponta'],
            ]),
            $infoExtra,
            $analise,
        );

        return redirect()->route('consultor.orcamentos.show', $id)
            ->with('success', "Orçamento Grupo {$data['grupo_tarifario']} criado com sucesso!");
    }
}
