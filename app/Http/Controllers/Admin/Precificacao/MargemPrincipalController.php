<?php

namespace App\Http\Controllers\Admin\Precificacao;

use App\Http\Controllers\Controller;
use App\Models\MargemPrincipal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MargemPrincipalController extends Controller
{
    public function index(): Response
    {
        $faixas = MargemPrincipal::orderBy('potencia_min')->get();

        return Inertia::render('Admin/Precificacao/MargemPrincipal/Index', [
            'faixas' => $faixas,
        ]);
    }

    public function store(Request $request): RedirectResponse
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

    public function update(Request $request, MargemPrincipal $margemPrincipal): RedirectResponse
    {
        $data = $request->validate([
            'nome'         => 'required|string|max:255',
            'potencia_min' => 'required|numeric|min:0',
            'potencia_max' => 'nullable|numeric|gt:potencia_min',
            'margem'       => 'required|numeric|min:0|max:100',
            'ordem'        => 'required|integer|min:0',
        ]);

        $margemPrincipal->update($data);

        return back()->with('success', 'Faixa atualizada com sucesso.');
    }

    public function destroy(MargemPrincipal $margemPrincipal): RedirectResponse
    {
        $margemPrincipal->delete();

        return back()->with('success', 'Faixa removida.');
    }
}
