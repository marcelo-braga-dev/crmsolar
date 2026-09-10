<?php

namespace App\Http\Controllers\Consultor\GrupoTarifario;

use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Grupo B2 — Rural (Baixa Tensão)
 *
 * Tarifa subsidiada para propriedades rurais (ANEEL).
 * Desconto aplicado via subclasse tarifária (L1/L2/L3/L4) ou tarifa fixa.
 * Aplicações típicas: residencial rural, produtivo rural, irrigação/bombeamento.
 */
class GrupoB2Controller extends BaseGrupoController
{
    public function create(): Response
    {
        return Inertia::render('Consultor/Orcamentos/GrupoB2', $this->dadosComuns());
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
            'tipo_instalacao'      => 'required|in:residencial_rural,produtivo,irrigacao',
            'possui_bombeamento'   => 'required|boolean',
            'consumo_bombeamento'  => 'nullable|numeric|min:0',
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
        $tarifa     = (float) $data['tarifa_kwh'];

        // Para irrigação, o consumo de bombeamento some da conta (é atendido por painel separado)
        $consumoResidual = (float) $data['consumo'];
        $consumoBomba    = (float) ($data['consumo_bombeamento'] ?? 0);
        $consumoTotal    = $consumoResidual + ($data['possui_bombeamento'] ? $consumoBomba : 0);

        $consumoDimensionar = $consumoTotal * ($data['objetivo_percentual'] / 100);
        $potencia = $this->dimensionamento->calcularPotencia($consumoDimensionar, $hsp, $params, $orientacao);

        $categorias = $data['categorias'] ?? [];
        // B2 irrigação → preferência por kits de bomba solar
        if ($data['tipo_instalacao'] === 'irrigacao' && empty($categorias)) {
            $categorias = ['bomba', 'ongrid'];
        }

        $kits   = $this->dimensionamento->buscarKits($potencia, (int) $data['estrutura_id'], (int) $data['tensao'], (int) $data['qtd_kits'], $categorias);
        $estado = $cliente->cidade->sigla ?? $cliente->cidade->estado;
        $qtd    = (int) $data['qtd_kits'];
        $pr     = $this->dimensionamento->detalhamentoPR($params, $orientacao);

        if ($kits->isEmpty()) {
            return response()->json(['error' => 'Nenhum kit encontrado. Ajuste a estrutura, tensão ou consumo.'], 404);
        }

        $kitsResult = $this->mapearKits($kits, $qtd, $hsp, $params, $orientacao, $estado, (int) $data['estrutura_id']);

        $kitsResult = $kitsResult->map(function ($kit) use ($consumoTotal, $tarifa, $data) {
            $economia = $this->grupoService->economiaGrupoB(
                $kit['geracao'], $consumoTotal, $tarifa,
                $data['fases'], (int) $data['objetivo_percentual'],
            );
            $analise = $this->grupoService->analiseCompleta($kit['preco_venda'], $economia['economia_mensal']);
            return array_merge($kit, [
                'economia'           => $economia,
                'payback_simples'    => $analise['payback_simples'],
                'payback_descontado' => $analise['payback_descontado'],
                'vpl_25a'            => $analise['vpl_25a'],
                'tir_25a'            => $analise['tir_25a'],
                'economia_total_25a' => $analise['economia_total_25a'],
                'roi_percentual'     => $analise['roi_percentual'],
            ]);
        });

        $analiseMensal = $this->dimensionamento->analiseMensal(
            $cliente->cidade_id,
            (float) $kits->first()->potencia_kwp * $qtd,
            $consumoTotal, $params, $orientacao,
        );

        return response()->json([
            'potencia_calculada'  => $potencia,
            'hsp'                 => $hsp,
            'pr'                  => $pr,
            'kits'                => $kitsResult,
            'analise_mensal'      => $analiseMensal,
            'consumo_total'       => $consumoTotal,
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
            'tipo_instalacao'      => 'required|in:residencial_rural,produtivo,irrigacao',
            'possui_bombeamento'   => 'required|boolean',
            'consumo_bombeamento'  => 'nullable|numeric|min:0',
            'kit_id'               => 'required|exists:kits,id',
            'anotacoes'            => 'nullable|string|max:3000',
            'anotacoes_tecnicas'   => 'nullable|string|max:3000',
        ]);

        $consumoTotal = (float) $data['consumo'] + ($data['possui_bombeamento'] ? (float) ($data['consumo_bombeamento'] ?? 0) : 0);
        $geracao      = (int) $request->input('geracao_estimada', 0);
        $economia     = $this->grupoService->economiaGrupoB($geracao, $consumoTotal, (float) $data['tarifa_kwh'], $data['fases'], (int) $data['objetivo_percentual']);
        $analise      = $this->grupoService->analiseCompleta((float) $request->input('preco_venda', 0), $economia['economia_mensal']);

        $infoExtra = [
            'tarifa_kwh'          => $data['tarifa_kwh'],
            'valor_conta_mensal'  => $data['valor_conta_mensal'] ?? null,
            'fases'               => $data['fases'],
            'disponibilidade_kwh' => \App\Services\GrupoTarifarioService::disponibilidadePorFase($data['fases']),
            'objetivo_percentual' => (int) $data['objetivo_percentual'],
            'tipo_instalacao'     => $data['tipo_instalacao'],
            'possui_bombeamento'  => (bool) $data['possui_bombeamento'],
            'consumo_bombeamento' => $data['consumo_bombeamento'] ?? null,
        ];

        $id = $this->salvarOrcamento(
            array_merge($data, ['consumo' => $consumoTotal, 'grupo_tarifario' => 'B2', 'modalidade_tarifaria' => 'convencional']),
            $infoExtra,
            $analise,
        );

        return redirect()->route('consultor.orcamentos.show', $id)
            ->with('success', 'Orçamento B2 Rural criado com sucesso!');
    }
}
