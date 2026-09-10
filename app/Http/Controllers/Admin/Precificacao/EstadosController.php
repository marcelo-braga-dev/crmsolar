<?php

namespace App\Http\Controllers\Admin\Precificacao;

use App\Http\Controllers\Controller;
use App\Models\MargemEstado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EstadosController extends Controller
{
    private const ESTADOS_BR = [
        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
        'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
        'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
        'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
        'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
    ];

    public function index(): Response
    {
        $dbMargens = MargemEstado::all()->keyBy('estado');

        $estados = collect(self::ESTADOS_BR)->map(function ($nome, $uf) use ($dbMargens) {
            $row = $dbMargens->get($uf);
            return [
                'id'         => $row?->id,
                'estado'     => $uf,
                'nome_estado' => $nome,
                'margem'     => $row ? (float) $row->margem : 0.0,
            ];
        })->values();

        return Inertia::render('Admin/Precificacao/Estados/Index', [
            'estados' => $estados,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'estado' => ['required', 'string', 'size:2', 'in:' . implode(',', array_keys(self::ESTADOS_BR))],
            'margem' => 'required|numeric|min:0|max:100',
        ]);

        MargemEstado::updateOrCreate(
            ['estado' => $data['estado']],
            [
                'nome_estado' => self::ESTADOS_BR[$data['estado']],
                'margem'      => $data['margem'],
            ]
        );

        return back()->with('success', 'Margem atualizada.');
    }
}
