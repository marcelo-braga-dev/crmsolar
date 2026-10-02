<?php

namespace App\Http\Controllers\Consultor\Dimensionamento;

use App\Http\Controllers\Consultor\GrupoTarifario\BaseGrupoController;
use App\Http\Requests\Consultor\Dimensionamento\ConvencionalRequest;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dimensionamento convencional (Grupo B) a partir do consumo — ou da potência em kWp
 * informada diretamente. Busca de kits e persistência vêm do BaseGrupoController.
 */
class ConvencionalController extends BaseGrupoController
{
    public function create(): Response
    {
        return Inertia::render('Consultor/Orcamentos/Create', ['tipo' => 'convencional'] + $this->dadosComuns());
    }

    public function buscarKits(ConvencionalRequest $request): JsonResponse
    {
        $data = $request->validated();

        $cliente = Cliente::with('cidade')->findOrFail($data['cliente_id']);
        if (! $cliente->cidade_id) {
            return response()->json(['error' => 'Cliente sem cidade cadastrada. Edite o cliente e informe a cidade antes de dimensionar.'], 422);
        }

        $params = $this->dimensionamento->getParams();
        $hsp = $this->dimensionamento->getIrradiacao($cliente->cidade_id);
        $orientacao = $data['orientacao'];
        $qtd = (int) $data['qtd_kits'];

        // Modo kWp direto: usa a potência informada, sem calcular pelo consumo.
        $potencia = ! empty($data['kwp_direto'])
            ? (float) $data['kwp_direto']
            : $this->dimensionamento->calcularPotencia((float) $data['consumo'], $hsp, $params, $orientacao);

        $kits = $this->dimensionamento->buscarKits($potencia, (int) $data['estrutura_id'], (int) $data['tensao'], $qtd, $data['categorias'] ?? []);
        $estado = $cliente->cidade->sigla ?? $cliente->cidade->estado;

        return response()->json([
            'potencia_calculada' => $potencia,
            'hsp' => $hsp,
            'pr' => $this->dimensionamento->detalhamentoPR($params, $orientacao),
            'kits' => $this->mapearKits($kits, $qtd, $hsp, $params, $orientacao, $estado),
            'analise_mensal' => $kits->isNotEmpty()
                ? $this->dimensionamento->analiseMensal($cliente->cidade_id, (float) $kits->first()->potencia_kwp * $qtd, (float) $data['consumo'], $params, $orientacao)
                : null,
        ]);
    }

    public function store(ConvencionalRequest $request): RedirectResponse
    {
        $id = $this->salvarOrcamento($request->validated(), [], null);

        return redirect()->route('consultor.orcamentos.show', $id)
            ->with('success', 'Orçamento criado com sucesso!');
    }
}
