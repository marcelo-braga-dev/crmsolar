<?php

namespace App\Http\Controllers\Admin\Produtos;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarcasController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255|unique:marcas,nome',
            'url_logo' => 'nullable|url|max:500',
            'ativo' => 'boolean',
        ]);

        Marca::create($data);

        return back()->with('success', 'Marca criada com sucesso.');
    }

    public function update(Request $request, Marca $marca): RedirectResponse
    {
        $data = $request->validate([
            'nome' => "required|string|max:255|unique:marcas,nome,{$marca->id}",
            'url_logo' => 'nullable|url|max:500',
            'ativo' => 'boolean',
        ]);

        $marca->update($data);

        return back()->with('success', 'Marca atualizada com sucesso.');
    }

    public function destroy(Marca $marca): RedirectResponse
    {
        if ($marca->produtos()->exists()) {
            return back()->with('error', 'Não é possível excluir marca com produtos vinculados.');
        }

        $marca->delete();

        return back()->with('success', 'Marca removida.');
    }
}
