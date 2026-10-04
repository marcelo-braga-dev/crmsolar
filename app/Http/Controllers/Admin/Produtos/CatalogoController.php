<?php

namespace App\Http\Controllers\Admin\Produtos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProdutoRequest;
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
    /** Abas da página única de produtos avulsos (antes eram páginas separadas no menu). */
    public const ABAS = ['produtos', 'categorias', 'marcas'];

    public function index(Request $request): Response
    {
        $aba = in_array($request->aba, self::ABAS, true) ? $request->aba : 'produtos';

        // Atalhos Painéis / Inversores / Transformadores... = filtro pelo slug da categoria.
        $produtos = Produto::with(['categoria:id,nome,slug', 'marca:id,nome', 'fornecedor:id,nome'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('nome', 'like', "%{$s}%")->orWhere('modelo', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->when($request->categoria, fn ($q, $slug) => $q->whereHas('categoria', fn ($q) => $q->where('slug', $slug)))
            ->when($request->fornecedor_id, fn ($q, $f) => $q->where('fornecedor_id', $f))
            ->when($request->filled('ativo'), fn ($q) => $q->where('ativo', $request->boolean('ativo')))
            ->orderBy('nome')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Produtos/Catalogo/Index', [
            'aba' => $aba,
            'produtos' => $produtos,
            'totalProdutos' => Produto::count(),
            // Todas (inclusive inativas): a aba Categorias gerencia todas; os atalhos mostram só as ativas.
            'categorias' => CategoriaProduto::withCount('produtos')->orderBy('ordem')->orderBy('nome')->get(),
            'marcas' => Marca::withCount('produtos')->orderBy('nome')->get(),
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'filters' => $request->only(['search', 'categoria', 'fornecedor_id', 'ativo']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Produtos/Catalogo/Form', [
            ...$this->opcoesFormulario(),
            // "Novo produto" a partir de um atalho (ex.: Inversores) já vem com a categoria escolhida.
            'categoriaInicial' => $request->categoria
                ? CategoriaProduto::where('slug', $request->categoria)->value('id')
                : null,
        ]);
    }

    public function store(ProdutoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
            'produto' => $catalogo,
            ...$this->opcoesFormulario(),
        ]);
    }

    public function update(ProdutoRequest $request, Produto $catalogo): RedirectResponse
    {
        $validated = $request->validated();

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

    private function opcoesFormulario(): array
    {
        return [
            'categorias' => CategoriaProduto::where('ativo', true)->orderBy('ordem')->get(['id', 'nome', 'slug', 'eh_componente_kit', 'exige_potencia', 'icone']),
            'marcas' => Marca::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ];
    }
}
