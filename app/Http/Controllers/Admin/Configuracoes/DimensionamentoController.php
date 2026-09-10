<?php

namespace App\Http\Controllers\Admin\Configuracoes;

use App\Http\Controllers\Controller;
use App\Models\ParamDimensionamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DimensionamentoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Configuracoes/Dimensionamento/Index', [
            'params' => ParamDimensionamento::orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'params'          => 'required|array',
            'params.*.id'     => 'required|exists:params_dimensionamento,id',
            'params.*.valor'  => 'required|string|max:255',
        ]);

        foreach ($data['params'] as $item) {
            ParamDimensionamento::where('id', $item['id'])->update(['valor' => $item['valor']]);
        }

        return back()->with('success', 'Parâmetros de dimensionamento atualizados.');
    }
}
