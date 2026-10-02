<?php

namespace App\Http\Controllers\Consultor\Dimensionamento;

use App\Http\Controllers\Consultor\GrupoTarifario\BaseGrupoController;
use App\Http\Requests\Consultor\Dimensionamento\DemandaRequest;
use App\Models\Cliente;
use App\Models\Concessionaria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dimensionamento por demanda (horo-sazonal): consumo de ponta e fora de ponta,
 * ponderados pelas tarifas da concessionária. Busca de kits e persistência vêm do BaseGrupoController.
 */
class DemandaController extends BaseGrupoController
{
    public function create(): Response
    {
        return Inertia::render('Consultor/Orcamentos/Create', ['tipo' => 'demanda'] + $this->dadosComuns());
    }

    public function buscarKits(DemandaRequest $request): JsonResponse
    {
        $data = $request->validated();

        $cliente = Cliente::with('cidade')->findOrFail($data['cliente_id']);
        if (! $cliente->cidade_id) {
            return response()->json(['error' => 'Cliente sem cidade cadastrada. Edite o cliente e informe a cidade antes de dimensionar.'], 422);
        }

        $concessionaria = Concessionaria::findOrFail($data['concessionaria_id']);
        $params = $this->dimensionamento->getParams();
        $hsp = $this->dimensionamento->getIrradiacao($cliente->cidade_id);
        $orientacao = $data['orientacao'];
        $qtd = (int) $data['qtd_kits'];

        $potencia = $this->dimensionamento->calcularPotenciaDemanda(
            (float) $data['consumo_ponta'],
            (float) $data['consumo_fora_ponta'],
            (float) $concessionaria->tarifa_ponta,
            (float) $concessionaria->tarifa_fora_ponta,
            $hsp, $params, $orientacao,
        );

        $kits = $this->dimensionamento->buscarKits($potencia, (int) $data['estrutura_id'], (int) $data['tensao'], $qtd, $data['categorias'] ?? []);
        $estado = $cliente->cidade->sigla ?? $cliente->cidade->estado;
        $consumoTotal = (float) $data['consumo_ponta'] + (float) $data['consumo_fora_ponta'];

        return response()->json([
            'potencia_calculada' => $potencia,
            'hsp' => $hsp,
            'pr' => $this->dimensionamento->detalhamentoPR($params, $orientacao),
            'kits' => $this->mapearKits($kits, $qtd, $hsp, $params, $orientacao, $estado),
            'analise_mensal' => $kits->isNotEmpty()
                ? $this->dimensionamento->analiseMensal($cliente->cidade_id, (float) $kits->first()->potencia_kwp * $qtd, $consumoTotal, $params, $orientacao)
                : null,
        ]);
    }

    public function store(DemandaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $concessionaria = Concessionaria::findOrFail($data['concessionaria_id']);

        $id = $this->salvarOrcamento(
            ['consumo' => (float) $data['consumo_ponta'] + (float) $data['consumo_fora_ponta']] + $data,
            [
                'tipo_dimensionamento' => 'demanda',
                'concessionaria_id' => $concessionaria->id,
                'consumo_ponta' => $data['consumo_ponta'],
                'consumo_fora_ponta' => $data['consumo_fora_ponta'],
                'metadados' => [
                    'concessionaria' => $concessionaria->nome,
                    'tarifa_ponta' => $concessionaria->tarifa_ponta,
                    'tarifa_fora_ponta' => $concessionaria->tarifa_fora_ponta,
                    'fc' => round((float) $concessionaria->tarifa_ponta / max((float) $concessionaria->tarifa_fora_ponta, 0.0001), 3),
                ],
            ],
            null,
        );

        return redirect()->route('consultor.orcamentos.show', $id)
            ->with('success', 'Orçamento criado com sucesso!');
    }
}
