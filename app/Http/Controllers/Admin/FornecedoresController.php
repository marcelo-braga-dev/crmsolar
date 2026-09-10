<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fornecedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FornecedoresController extends Controller
{
    public function index(Request $request): Response
    {
        $fornecedores = Fornecedor::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nome', 'like', "%{$s}%")
                  ->orWhere('cnpj', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            }))
            ->withCount('kits')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Fornecedores/Index', [
            'fornecedores' => $fornecedores,
            'filters'      => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Fornecedores/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        Fornecedor::create($data);
        return redirect()->route('admin.fornecedores.index')
            ->with('success', 'Fornecedor criado com sucesso.');
    }

    public function show(Fornecedor $fornecedor): Response
    {
        $fornecedor->load(['kits' => fn ($q) => $q->latest()->limit(10)]);

        return Inertia::render('Admin/Fornecedores/Show', [
            'fornecedor' => $fornecedor,
        ]);
    }

    public function edit(Fornecedor $fornecedor): Response
    {
        return Inertia::render('Admin/Fornecedores/Form', [
            'fornecedor' => $fornecedor,
        ]);
    }

    public function update(Request $request, Fornecedor $fornecedor): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $fornecedor->update($data);
        return redirect()->route('admin.fornecedores.index')
            ->with('success', 'Fornecedor atualizado com sucesso.');
    }

    public function destroy(Fornecedor $fornecedor): RedirectResponse
    {
        $fornecedor->delete();
        return redirect()->route('admin.fornecedores.index')
            ->with('success', 'Fornecedor removido.');
    }

    private function rules(): array
    {
        return [
            'nome'          => 'required|string|max:255',
            'cnpj'          => 'nullable|string|max:18',
            'email'         => 'nullable|email|max:255',
            'telefone'      => 'nullable|string|max:20',
            'celular'       => 'nullable|string|max:20',
            'representante' => 'nullable|string|max:255',
            'site'          => 'nullable|url|max:255',
            'margem_padrao' => 'nullable|numeric|min:0|max:100',
            'anotacoes'     => 'nullable|string',
            'ativo'         => 'required|boolean',
        ];
    }
}
