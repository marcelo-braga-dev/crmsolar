<?php

namespace App\Http\Controllers\Admin\Produtos;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProduto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoriasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Produtos/Categorias/Index', [
            'categorias' => CategoriaProduto::withCount('produtos')->orderBy('ordem')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome'              => 'required|string|max:100',
            'slug'              => 'required|string|max:60|unique:categorias_produtos,slug',
            'descricao'         => 'nullable|string|max:200',
            'icone'             => 'nullable|string|max:80',
            'eh_componente_kit' => 'boolean',
            'exige_potencia'    => 'boolean',
            'ordem'             => 'integer|min:0',
        ]);

        CategoriaProduto::create(array_merge($validated, ['ativo' => true]));

        return back()->with('success', 'Categoria criada.');
    }

    public function update(Request $request, CategoriaProduto $categoria): RedirectResponse
    {
        $validated = $request->validate([
            'nome'              => 'required|string|max:100',
            'descricao'         => 'nullable|string|max:200',
            'icone'             => 'nullable|string|max:80',
            'eh_componente_kit' => 'boolean',
            'exige_potencia'    => 'boolean',
            'ativo'             => 'boolean',
            'ordem'             => 'integer|min:0',
        ]);

        $categoria->update($validated);

        return back()->with('success', 'Categoria atualizada.');
    }

    public function destroy(CategoriaProduto $categoria): RedirectResponse
    {
        if ($categoria->produtos()->exists()) {
            return back()->with('error', 'Não é possível remover uma categoria com produtos cadastrados.');
        }

        $categoria->delete();

        return back()->with('success', 'Categoria removida.');
    }
}
