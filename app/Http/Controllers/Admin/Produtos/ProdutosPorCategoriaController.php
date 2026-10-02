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

/**
 * CRUD de produtos do catálogo restrito a uma categoria (inversores, painéis, trafos).
 * Cada subclasse só declara a categoria e os nomes usados nas páginas e rotas.
 */
abstract class ProdutosPorCategoriaController extends Controller
{
    /** Slug da categoria em categorias_produtos (ver CategoriasProdutosSeeder). */
    abstract protected function slug(): string;

    /** Nome usado se a categoria precisar ser criada. */
    abstract protected function nomeCategoria(): string;

    /** Pasta das páginas em resources/js/Pages/Admin/Produtos e sufixo das rotas (ex.: "Inversores" / "inversores"). */
    abstract protected function pagina(): string;

    /** Nome da prop do registro no formulário (ex.: "inversor"). */
    abstract protected function propRegistro(): string;

    /** Mensagens de flash: [criado, atualizado, removido]. */
    abstract protected function mensagens(): array;

    protected function categoria(): CategoriaProduto
    {
        return CategoriaProduto::firstOrCreate(
            ['slug' => $this->slug()],
            ['nome' => $this->nomeCategoria(), 'ativo' => true],
        );
    }

    private function rotaIndex(): string
    {
        return 'admin.produtos.'.strtolower($this->pagina()).'.index';
    }

    private function garantirCategoria(Produto $produto): void
    {
        abort_unless($produto->categoria_id === $this->categoria()->id, 404);
    }

    private function opcoes(): array
    {
        return [
            'marcas' => Marca::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ];
    }

    public function index(Request $request): Response
    {
        $categoriaId = $this->categoria()->id;

        $produtos = Produto::query()
            ->where('categoria_id', $categoriaId)
            ->with(['marca:id,nome', 'fornecedor:id,nome'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('nome', 'like', "%{$s}%")
                ->orWhere('modelo', 'like', "%{$s}%")
                ->orWhere('sku', 'like', "%{$s}%")))
            ->when($request->marca_id, fn ($q, $id) => $q->where('marca_id', $id))
            ->when($request->fornecedor_id, fn ($q, $id) => $q->where('fornecedor_id', $id))
            ->when($request->filled('ativo'), fn ($q) => $q->where('ativo', $request->boolean('ativo')))
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render("Admin/Produtos/{$this->pagina()}/Index", [
            strtolower($this->pagina()) => $produtos,
            'filters' => $request->only(['search', 'marca_id', 'fornecedor_id', 'ativo']),
            'stats' => [
                'total' => Produto::where('categoria_id', $categoriaId)->count(),
                'ativos' => Produto::where('categoria_id', $categoriaId)->where('ativo', true)->count(),
            ],
            ...$this->opcoes(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render("Admin/Produtos/{$this->pagina()}/Form", $this->opcoes());
    }

    public function store(Request $request): RedirectResponse
    {
        Produto::create($request->validate($this->rules()) + ['categoria_id' => $this->categoria()->id]);

        return redirect()->route($this->rotaIndex())->with('success', $this->mensagens()[0]);
    }

    public function edit(Produto $produto): Response
    {
        $this->garantirCategoria($produto);

        return Inertia::render("Admin/Produtos/{$this->pagina()}/Form", [
            $this->propRegistro() => $produto,
            ...$this->opcoes(),
        ]);
    }

    public function update(Request $request, Produto $produto): RedirectResponse
    {
        $this->garantirCategoria($produto);

        $produto->update($request->validate($this->rules()));

        return redirect()->route($this->rotaIndex())->with('success', $this->mensagens()[1]);
    }

    public function destroy(Produto $produto): RedirectResponse
    {
        $this->garantirCategoria($produto);

        $produto->delete();

        return redirect()->route($this->rotaIndex())->with('success', $this->mensagens()[2]);
    }

    protected function rules(): array
    {
        return [
            'marca_id' => 'nullable|exists:marcas,id',
            'fornecedor_id' => 'nullable|exists:fornecedores,id',
            'nome' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'descricao' => 'nullable|string',
            'potencia' => 'nullable|numeric|min:0',
            'unidade_potencia' => 'nullable|string|max:10',
            'tensao' => 'nullable|integer|min:0',
            'preco_custo' => 'nullable|numeric|min:0',
            'garantia' => 'nullable|integer|min:0',
            'ativo' => 'boolean',
        ];
    }
}
