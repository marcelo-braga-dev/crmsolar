<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultor\ClienteRequest;
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

    public function store(ClienteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['consultor_id'] = Auth::id();

        Cliente::create($data);

        return redirect()->route('consultor.clientes.index')
            ->with('success', 'Cliente criado com sucesso.');
    }

    public function show(Cliente $cliente): Response
    {
        $this->authorize('view', $cliente);

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
        $this->authorize('update', $cliente);

        return Inertia::render('Consultor/Clientes/Form', [
            'cliente' => $cliente->load('cidade:id,cidade,estado'),
        ]);
    }

    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return redirect()->route('consultor.clientes.index')
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $this->authorize('delete', $cliente);

        $cliente->delete();

        return redirect()->route('consultor.clientes.index')
            ->with('success', 'Cliente removido.');
    }
}
