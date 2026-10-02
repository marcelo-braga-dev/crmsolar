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

class InversoresController extends Controller
{
    private function categoriaId(): int
    {
        return CategoriaProduto::firstOrCreate(
            ['slug' => 'inversor'],
            ['nome' => 'Inversor', 'ativo' => true]
        )->id;
    }

    public function index(Request $request): Response
    {
        $catId = $this->categoriaId();

        $inversores = Produto::query()
            ->where('categoria_id', $catId)
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

        return Inertia::render('Admin/Produtos/Inversores/Index', [
            'inversores' => $inversores,
            'filters' => $request->only(['search', 'marca_id', 'fornecedor_id', 'ativo']),
            'marcas' => Marca::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'stats' => [
                'total' => Produto::where('categoria_id', $catId)->count(),
                'ativos' => Produto::where('categoria_id', $catId)->where('ativo', true)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Produtos/Inversores/Form', [
            'marcas' => Marca::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['categoria_id'] = $this->categoriaId();

        Produto::create($data);

        return redirect()->route('admin.produtos.inversores.index')
            ->with('success', 'Inversor criado com sucesso.');
    }

    public function edit(Produto $inversor): Response
    {
        return Inertia::render('Admin/Produtos/Inversores/Form', [
            'inversor' => $inversor,
            'marcas' => Marca::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function update(Request $request, Produto $inversor): RedirectResponse
    {
        $inversor->update($request->validate($this->rules()));

        return redirect()->route('admin.produtos.inversores.index')
            ->with('success', 'Inversor atualizado com sucesso.');
    }

    public function destroy(Produto $inversor): RedirectResponse
    {
        $inversor->delete();

        return redirect()->route('admin.produtos.inversores.index')
            ->with('success', 'Inversor removido.');
    }

    private function rules(): array
    {
        return [
            'marca_id' => 'nullable|exists:marcas,id',
            'fornecedor_id' => 'nullable|exists:fornecedores,id',
            'nome' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'descricao' => 'nullable|string',
            'potencia' => 'nullable|numeric|min:0',
            'unidade_potencia' => 'nullable|string|max:20',
            'tensao' => 'nullable|string|max:50',
            'preco_custo' => 'nullable|numeric|min:0',
            'garantia' => 'nullable|integer|min:0',
            'ativo' => 'boolean',
        ];
    }
}
