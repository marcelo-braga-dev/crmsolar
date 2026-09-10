<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CidadeEstado;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientesController extends Controller
{
    public function index(Request $request): Response
    {
        $clientes = Cliente::query()
            ->with(['consultor:id,name', 'cidade:id,cidade,estado'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nome', 'like', "%{$s}%")
                  ->orWhere('razao_social', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('cpf', 'like', "%{$s}%")
                  ->orWhere('cnpj', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->consultor_id, fn ($q, $id) => $q->where('consultor_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Clientes/Index', [
            'clientes' => $clientes,
            'filters' => $request->only(['search', 'status', 'consultor_id']),
            'consultores' => User::where('tipo', '!=', 'admin')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Clientes/Form', [
            'consultores' => User::where('tipo', '!=', 'admin')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        Cliente::create($data);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente criado com sucesso.');
    }

    public function show(Cliente $cliente): Response
    {
        $cliente->load([
            'consultor:id,name',
            'cidade:id,cidade,estado',
            'orcamentos' => fn ($q) => $q->latest()->limit(10)->with('cidade:id,cidade,estado'),
            'visitas' => fn ($q) => $q->latest()->limit(5),
        ]);

        return Inertia::render('Admin/Clientes/Show', [
            'cliente' => $cliente,
        ]);
    }

    public function edit(Cliente $cliente): Response
    {
        return Inertia::render('Admin/Clientes/Form', [
            'cliente' => $cliente->load('cidade:id,cidade,estado'),
            'consultores' => User::where('tipo', '!=', 'admin')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $data = $request->validate($this->rules($cliente->id));

        $cliente->update($data);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->delete();

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente removido.');
    }

    private function rules(?int $id = null): array
    {
        return [
            'consultor_id' => 'required|exists:users,id',
            'cidade_id' => 'nullable|exists:cidades_estados,id',
            'tipo_pessoa' => 'required|in:pf,pj',
            'nome' => 'nullable|string|max:255',
            'razao_social' => 'nullable|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'cnpj' => 'nullable|string|max:18',
            'rg' => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:9',
            'rua' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'status' => 'required|in:novo,orcamento_gerado,visita_agendada,finalizado',
            'anotacoes' => 'nullable|string',
        ];
    }
}
