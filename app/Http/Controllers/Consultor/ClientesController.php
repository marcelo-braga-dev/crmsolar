<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClientesController extends Controller
{
    public function index(Request $request): Response
    {
        $clientes = Cliente::query()
            ->where('consultor_id', Auth::id())
            ->with('cidade:id,cidade,estado')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nome', 'like', "%{$s}%")
                  ->orWhere('razao_social', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('telefone', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Consultor/Clientes/Index', [
            'clientes' => $clientes,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Consultor/Clientes/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['consultor_id'] = Auth::id();

        Cliente::create($data);

        return redirect()->route('consultor.clientes.index')
            ->with('success', 'Cliente criado com sucesso.');
    }

    public function show(Cliente $cliente): Response
    {
        $this->authorizeCliente($cliente);

        $cliente->load([
            'cidade:id,cidade,estado',
            'orcamentos' => fn ($q) => $q->latest()->limit(10)->with('cidade:id,cidade,estado'),
            'visitas' => fn ($q) => $q->latest()->limit(5),
        ]);

        return Inertia::render('Consultor/Clientes/Show', [
            'cliente' => $cliente,
        ]);
    }

    public function edit(Cliente $cliente): Response
    {
        $this->authorizeCliente($cliente);

        return Inertia::render('Consultor/Clientes/Form', [
            'cliente' => $cliente->load('cidade:id,cidade,estado'),
        ]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $this->authorizeCliente($cliente);

        $cliente->update($request->validate($this->rules()));

        return redirect()->route('consultor.clientes.index')
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $this->authorizeCliente($cliente);

        $cliente->delete();

        return redirect()->route('consultor.clientes.index')
            ->with('success', 'Cliente removido.');
    }

    private function authorizeCliente(Cliente $cliente): void
    {
        if ($cliente->consultor_id !== Auth::id()) {
            abort(403);
        }
    }

    private function rules(): array
    {
        return [
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
