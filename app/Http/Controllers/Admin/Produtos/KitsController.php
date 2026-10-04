<?php

namespace App\Http\Controllers\Admin\Produtos;

use App\Http\Controllers\Controller;
use App\Models\Estrutura;
use App\Models\Fornecedor;
use App\Models\Kit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KitsController extends Controller
{
    public function index(Request $request): Response
    {
        $kits = Kit::query()
            ->with(['fornecedor:id,nome', 'estrutura:id,nome'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('nome', 'like', "%{$s}%")
                ->orWhere('modelo', 'like', "%{$s}%")
                ->orWhere('sku', 'like', "%{$s}%")))
            ->when($request->fornecedor_id, fn ($q, $id) => $q->where('fornecedor_id', $id))
            ->when($request->estrutura_id, fn ($q, $id) => $q->where('estrutura_id', $id))
            ->when($request->potencia_min, fn ($q, $v) => $q->where('potencia_kwp', '>=', $v))
            ->when($request->potencia_max, fn ($q, $v) => $q->where('potencia_kwp', '<=', $v))
            ->when($request->filled('ativo'), fn ($q) => $q->where('ativo', $request->boolean('ativo')))
            ->orderBy('potencia_kwp')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Produtos/Kits/Index', [
            'kits' => $kits,
            'filters' => $request->only(['search', 'fornecedor_id', 'estrutura_id', 'potencia_min', 'potencia_max', 'ativo']),
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'estruturas' => Estrutura::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'categorias' => Kit::CATEGORIAS,
            'stats' => [
                'total' => Kit::count(),
                'ativos' => Kit::where('ativo', true)->count(),
                'potencia_media' => round((float) Kit::where('ativo', true)->avg('potencia_kwp'), 2),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Produtos/Kits/Form', $this->opcoesFormulario());
    }

    public function store(Request $request): RedirectResponse
    {
        Kit::create($request->validate($this->rules($request)));

        return redirect()->route('admin.produtos.kits.index')
            ->with('success', 'Kit solar criado com sucesso.');
    }

    public function show(Kit $kit): Response
    {
        $kit->load(['fornecedor', 'estrutura', 'componentes.marca', 'componentes.categoria']);

        return Inertia::render('Admin/Produtos/Kits/Show', [
            'kit' => $kit,
            'categorias' => Kit::CATEGORIAS,
        ]);
    }

    public function edit(Kit $kit): Response
    {
        return Inertia::render('Admin/Produtos/Kits/Form', [
            'kit' => $kit,
            ...$this->opcoesFormulario(),
        ]);
    }

    public function update(Request $request, Kit $kit): RedirectResponse
    {
        $kit->update($request->validate($this->rules($request, $kit->id)));

        return redirect()->route('admin.produtos.kits.index')
            ->with('success', 'Kit solar atualizado com sucesso.');
    }

    public function destroy(Kit $kit): RedirectResponse
    {
        $kit->delete();

        return redirect()->route('admin.produtos.kits.index')
            ->with('success', 'Kit solar removido.');
    }

    private function opcoesFormulario(): array
    {
        return [
            'fornecedores' => Fornecedor::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'estruturas' => Estrutura::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'categorias' => Kit::CATEGORIAS,
            'tensoes' => Kit::TENSOES,
        ];
    }

    private function rules(Request $request, ?int $id = null): array
    {
        return [
            'fornecedor_id' => 'required|exists:fornecedores,id',
            'estrutura_id' => 'nullable|exists:estruturas,id',
            'nome' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            // Índice único (fornecedor_id, sku): sem esta regra o SKU repetido viraria erro 500.
            'sku' => ['nullable', 'string', 'max:60',
                Rule::unique('kits', 'sku')->where('fornecedor_id', $request->input('fornecedor_id'))->ignore($id)],
            'categoria' => ['required', Rule::in(array_keys(Kit::CATEGORIAS))],
            'potencia_kwp' => 'required|numeric|min:0.1',
            'tensao' => ['required', 'integer', Rule::in(Kit::TENSOES)],
            'inclui_trafo' => 'boolean',
            'preco_custo' => 'required|numeric|min:0',
            'ativo' => 'boolean',
            'ativo_fornecedor' => 'boolean',
            'observacoes' => 'nullable|string',
        ];
    }
}
