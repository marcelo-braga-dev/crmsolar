<?php

namespace App\Http\Controllers\Admin\Produtos;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProduto;
use App\Models\Fornecedor;
use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogoController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Produto::with(['categoria', 'marca', 'fornecedor'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('nome', 'like', "%{$s}%")->orWhere('modelo', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->when($request->categoria_id, fn ($q, $c) => $q->where('categoria_id', $c))
            ->when($request->fornecedor_id, fn ($q, $f) => $q->where('fornecedor_id', $f))
            ->when($request->ativo !== null, fn ($q) => $q->where('ativo', $request->boolean('ativo')))
            ->orderBy('nome');

        return Inertia::render('Admin/Produtos/Catalogo/Index', [
            'produtos'    => $query->paginate(30)->withQueryString(),
            'categorias'  => CategoriaProduto::where('ativo', true)->orderBy('ordem')->get(['id', 'nome', 'slug', 'eh_componente_kit', 'exige_potencia']),
            'marcas'      => Marca::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'filters'     => $request->only(['search', 'categoria_id', 'fornecedor_id', 'ativo']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Produtos/Catalogo/Form', [
            'categorias'  => CategoriaProduto::where('ativo', true)->orderBy('ordem')->get(['id', 'nome', 'slug', 'eh_componente_kit', 'exige_potencia', 'icone']),
            'marcas'      => Marca::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categoria_id'      => 'required|exists:categorias_produtos,id',
            'marca_id'          => 'nullable|exists:marcas,id',
            'fornecedor_id'     => 'nullable|exists:fornecedores,id',
            'nome'              => 'required|string|max:200',
            'modelo'            => 'nullable|string|max:100',
            'sku'               => 'nullable|string|max:60|unique:produtos,sku',
            'descricao'         => 'nullable|string',
            'potencia'          => 'nullable|numeric|min:0',
            'unidade_potencia'  => 'nullable|string|max:10',
            'tensao'            => 'nullable|integer',
            'unidade'           => 'nullable|string|max:20',
            'preco_custo'       => 'required|numeric|min:0',
            'garantia'          => 'nullable|string|max:100',
            'imagem_url'        => 'nullable|url|max:500',
            'ficha_tecnica_url' => 'nullable|url|max:500',
            'atributos'         => 'nullable|array',
            'ativo'             => 'boolean',
        ]);

        $produto = Produto::create($validated);

        return redirect()->route('admin.produtos.catalogo.show', $produto)
            ->with('success', 'Produto cadastrado com sucesso.');
    }

    public function show(Produto $catalogo): Response
    {
        return Inertia::render('Admin/Produtos/Catalogo/Show', [
            'produto' => $catalogo->load(['categoria', 'marca', 'fornecedor', 'kits.fornecedor']),
        ]);
    }

    public function edit(Produto $catalogo): Response
    {
        return Inertia::render('Admin/Produtos/Catalogo/Form', [
            'produto'     => $catalogo,
            'categorias'  => CategoriaProduto::where('ativo', true)->orderBy('ordem')->get(['id', 'nome', 'slug', 'eh_componente_kit', 'exige_potencia', 'icone']),
            'marcas'      => Marca::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function update(Request $request, Produto $catalogo): RedirectResponse
    {
        $validated = $request->validate([
            'categoria_id'      => 'required|exists:categorias_produtos,id',
            'marca_id'          => 'nullable|exists:marcas,id',
            'fornecedor_id'     => 'nullable|exists:fornecedores,id',
            'nome'              => 'required|string|max:200',
            'modelo'            => 'nullable|string|max:100',
            'sku'               => 'nullable|string|max:60|unique:produtos,sku,' . $catalogo->id,
            'descricao'         => 'nullable|string',
            'potencia'          => 'nullable|numeric|min:0',
            'unidade_potencia'  => 'nullable|string|max:10',
            'tensao'            => 'nullable|integer',
            'unidade'           => 'nullable|string|max:20',
            'preco_custo'       => 'required|numeric|min:0',
            'garantia'          => 'nullable|string|max:100',
            'imagem_url'        => 'nullable|url|max:500',
            'ficha_tecnica_url' => 'nullable|url|max:500',
            'atributos'         => 'nullable|array',
            'ativo'             => 'boolean',
        ]);

        $catalogo->update($validated);

        return redirect()->route('admin.produtos.catalogo.show', $catalogo)
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Produto $catalogo): RedirectResponse
    {
        $catalogo->delete();

        return redirect()->route('admin.produtos.catalogo.index')
            ->with('success', 'Produto removido.');
    }
}
