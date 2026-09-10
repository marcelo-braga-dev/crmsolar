<?php

namespace App\Http\Controllers\Admin\Configuracoes;

use App\Http\Controllers\Controller;
use App\Models\Banco;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BancosController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Configuracoes/Bancos/Index', [
            'bancos' => Banco::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome'         => 'required|string|max:255',
            'juros_mensal' => 'required|numeric|min:0|max:100',
            'qtd_parcelas' => 'required|integer|min:1|max:360',
            'carencia'     => 'nullable|integer|min:0|max:12',
            'ativo'        => 'required|boolean',
        ]);

        Banco::create($data);

        return back()->with('success', 'Banco criado com sucesso.');
    }

    public function update(Request $request, Banco $banco): RedirectResponse
    {
        $data = $request->validate([
            'nome'         => 'required|string|max:255',
            'juros_mensal' => 'required|numeric|min:0|max:100',
            'qtd_parcelas' => 'required|integer|min:1|max:360',
            'carencia'     => 'nullable|integer|min:0|max:12',
            'ativo'        => 'required|boolean',
        ]);

        $banco->update($data);

        return back()->with('success', 'Banco atualizado com sucesso.');
    }

    public function destroy(Banco $banco): RedirectResponse
    {
        $banco->delete();

        return back()->with('success', 'Banco removido.');
    }
}
