<?php

namespace App\Http\Controllers\Consultor\GrupoTarifario;

use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Grupo B1 — Residencial (Baixa Tensão)
 *
 * Tarifa convencional (monômia): uma única tarifa de energia para todas as horas.
 * Custo de disponibilidade mínimo conforme número de fases (30/50/100 kWh equivalentes).
 * Compensação de energia Lei 14.300/2022 (SIGA).
 */
class GrupoB1Controller extends BaseGrupoController
{
    public function create(): Response
    {
        return Inertia::render('Consultor/Orcamentos/GrupoB1', $this->dadosComuns());
    }

    public function calcular(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cliente_id'           => 'required|exists:clientes,id',
            'estrutura_id'         => 'required|exists:estruturas,id',
            'tensao'               => 'required|integer|in:127,220',
            'qtd_kits'             => 'required|integer|min:1|max:10',
            'orientacao'           => 'required|in:norte,nordeste_noroeste,leste_oeste,sudeste_sudoeste,sul',
            'fases'                => 'required|in:monofasico,bifasico,trifasico',
            'consumo'              => 'required|numeric|min:1',
            'tarifa_kwh'           => 'required|numeric|min:0.01',
            'valor_conta_mensal'   => 'nullable|numeric|min:0',
            'objetivo_percentual'  => 'required|integer|in:50,75,100',
            'categorias'           => 'nullable|array',
            'categorias.*'         => 'in:ongrid,offgrid,hibrido,bomba,microinversor',
        ]);

        $cliente = Cliente::with('cidade')->findOrFail($data['cliente_id']);
        if (!$cliente->cidade_id) {
            return response()->json(['error' => 'Cliente sem cidade cadastrada.'], 422);
        }

        $params     = $this->dimensionamento->getParams();
        $hsp        = $this->dimensionamento->getIrradiacao($cliente->cidade_id);
        $orientacao = $data['orientacao'];
        $consumo    = (float) $data['consumo'];
        $tarifa     = (float) $data['tarifa_kwh'];

        // Dimensionamento: ajusta pelo objetivo (100% → atende consumo total)
        $consumoDimensionar = $consumo * ($data['objetivo_percentual'] / 100);
        $potencia = $this->dimensionamento->calcularPotencia($consumoDimensionar, $hsp, $params, $orientacao);

        $categorias = $data['categorias'] ?? [];
        $kits       = $this->dimensionamento->buscarKits($potencia, (int) $data['estrutura_id'], (int) $data['tensao'], (int) $data['qtd_kits'], $categorias);
        $estado     = $cliente->cidade->sigla ?? $cliente->cidade->estado;
        $qtd        = (int) $data['qtd_kits'];
        $pr         = $this->dimensionamento->detalhamentoPR($params, $orientacao);

        $kitsResult = $this->mapearKits($kits, $qtd, $hsp, $params, $orientacao, $estado, (int) $data['estrutura_id']);

        // Análise econômica para cada kit
        $kitsResult = $kitsResult->map(function ($kit) use ($consumo, $tarifa, $data) {
            $economia = $this->grupoService->economiaGrupoB(
                $kit['geracao'], $consumo, $tarifa,
                $data['fases'], (int) $data['objetivo_percentual'],
            );
            $analise = $this->grupoService->analiseCompleta(
                $kit['preco_venda'], $economia['economia_mensal'],
            );
            return array_merge($kit, [
                'economia'             => $economia,
                'payback_simples'      => $analise['payback_simples'],
                'payback_descontado'   => $analise['payback_descontado'],
                'vpl_25a'              => $analise['vpl_25a'],
                'tir_25a'              => $analise['tir_25a'],
                'economia_total_25a'   => $analise['economia_total_25a'],
                'roi_percentual'       => $analise['roi_percentual'],
            ]);
        });

        $analiseMensal = $kitsResult->isNotEmpty()
            ? $this->dimensionamento->analiseMensal($cliente->cidade_id, (float) $kits->first()->potencia_kwp * $qtd, $consumo, $params, $orientacao)
            : null;

        if ($kitsResult->isEmpty()) {
            return response()->json(['error' => 'Nenhum kit encontrado. Ajuste a estrutura, tensão ou consumo.'], 404);
        }

        return response()->json([
            'potencia_calculada' => $potencia,
            'hsp'                => $hsp,
            'pr'                 => $pr,
            'kits'               => $kitsResult,
            'analise_mensal'     => $analiseMensal,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id'           => 'required|exists:clientes,id',
            'estrutura_id'         => 'required|exists:estruturas,id',
            'tensao'               => 'required|integer|in:127,220',
            'qtd_kits'             => 'required|integer|min:1|max:10',
            'orientacao'           => 'required|in:norte,nordeste_noroeste,leste_oeste,sudeste_sudoeste,sul',
            'fases'                => 'required|in:monofasico,bifasico,trifasico',
            'consumo'              => 'required|numeric|min:1',
            'tarifa_kwh'           => 'required|numeric|min:0.01',
            'valor_conta_mensal'   => 'nullable|numeric|min:0',
            'objetivo_percentual'  => 'required|integer|in:50,75,100',
            'kit_id'               => 'required|exists:kits,id',
            'anotacoes'            => 'nullable|string|max:3000',
            'anotacoes_tecnicas'   => 'nullable|string|max:3000',
        ]);

        $consumo  = (float) $data['consumo'];
        $tarifa   = (float) $data['tarifa_kwh'];
        $geracao  = (int) $request->input('geracao_estimada', 0);
        $economia = $this->grupoService->economiaGrupoB($geracao, $consumo, $tarifa, $data['fases'], (int) $data['objetivo_percentual']);
        $analise  = $this->grupoService->analiseCompleta((float) $request->input('preco_venda', 0), $economia['economia_mensal']);

        $infoExtra = [
            'tarifa_kwh'          => $tarifa,
            'valor_conta_mensal'  => $data['valor_conta_mensal'] ?? null,
            'fases'               => $data['fases'],
            'disponibilidade_kwh' => \App\Services\GrupoTarifarioService::disponibilidadePorFase($data['fases']),
            'objetivo_percentual' => (int) $data['objetivo_percentual'],
        ];

        $id = $this->salvarOrcamento(
            array_merge($data, ['grupo_tarifario' => 'B1', 'modalidade_tarifaria' => 'convencional']),
            $infoExtra,
            $analise,
        );

        return redirect()->route('consultor.orcamentos.show', $id)
            ->with('success', 'Orçamento B1 Residencial criado com sucesso!');
    }
}
