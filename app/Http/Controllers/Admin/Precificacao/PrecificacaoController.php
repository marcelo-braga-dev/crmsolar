<?php

namespace App\Http\Controllers\Admin\Precificacao;

use App\Http\Controllers\Controller;
use App\Models\Fornecedor;
use App\Models\MargemEstado;
use App\Models\MargemFornecedor;
use App\Models\MargemPrincipal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrecificacaoController extends Controller
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
        $faixas = MargemPrincipal::orderBy('potencia_min')->get();

        $dbMargensEstado = MargemEstado::all()->keyBy('estado');
        $estados = collect(self::ESTADOS_BR)->map(function ($nome, $uf) use ($dbMargensEstado) {
            $row = $dbMargensEstado->get($uf);

            return [
                'id'          => $row?->id,
                'estado'      => $uf,
                'nome_estado' => $nome,
                'margem'      => $row ? (float) $row->margem : 0.0,
            ];
        })->values();

        $fornecedores = Fornecedor::with('margemPrecificacao')
            ->orderBy('nome')
            ->get()
            ->map(fn ($f) => [
                'id'     => $f->id,
                'nome'   => $f->nome,
                'ativo'  => $f->ativo,
                'margem' => $f->margemPrecificacao ? (float) $f->margemPrecificacao->margem : 0.0,
            ]);

        return Inertia::render('Admin/Precificacao/Index', [
            'faixas'       => $faixas,
            'estados'      => $estados,
            'fornecedores' => $fornecedores,
        ]);
    }

    public function storeFaixa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome'         => 'required|string|max:255',
            'potencia_min' => 'required|numeric|min:0',
            'potencia_max' => 'nullable|numeric|gt:potencia_min',
            'margem'       => 'required|numeric|min:0|max:100',
            'ordem'        => 'required|integer|min:0',
        ]);

        MargemPrincipal::create($data);

        return back()->with('success', 'Faixa criada com sucesso.');
    }

    public function updateFaixa(Request $request, MargemPrincipal $faixa): RedirectResponse
    {
        $data = $request->validate([
            'nome'         => 'required|string|max:255',
            'potencia_min' => 'required|numeric|min:0',
            'potencia_max' => 'nullable|numeric|gt:potencia_min',
            'margem'       => 'required|numeric|min:0|max:100',
            'ordem'        => 'required|integer|min:0',
        ]);

        $faixa->update($data);

        return back()->with('success', 'Faixa atualizada com sucesso.');
    }

    public function destroyFaixa(MargemPrincipal $faixa): RedirectResponse
    {
        $faixa->delete();

        return back()->with('success', 'Faixa removida.');
    }

    public function updateEstado(Request $request): RedirectResponse
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

        return back()->with('success', 'Margem por estado atualizada.');
    }

    public function updateFornecedor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fornecedor_id' => 'required|integer|exists:fornecedores,id',
            'margem'        => 'required|numeric|min:0|max:100',
        ]);

        MargemFornecedor::updateOrCreate(
            ['fornecedor_id' => $data['fornecedor_id']],
            ['margem' => $data['margem']]
        );

        return back()->with('success', 'Margem por fornecedor atualizada.');
    }
}
