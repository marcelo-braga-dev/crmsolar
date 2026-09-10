<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\VisitaTecnica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VisitasController extends Controller
{
    public function index(Request $request): Response
    {
        $visitas = VisitaTecnica::query()
            ->where('consultor_id', Auth::id())
            ->with(['cliente', 'orcamento'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('data_agendada')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Consultor/Visitas/Index', [
            'visitas' => $visitas,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Consultor/Visitas/Form', [
            'clientes'   => Cliente::where('consultor_id', Auth::id())->orderBy('nome')->get(['id', 'nome', 'razao_social', 'tipo_pessoa']),
            'orcamentos' => Orcamento::where('consultor_id', Auth::id())->whereIn('status', ['aprovado', 'assinado'])->latest()->get(['id', 'cliente_id', 'preco_total', 'created_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id'    => 'required|exists:clientes,id',
            'orcamento_id'  => 'nullable|exists:orcamentos,id',
            'data_agendada' => 'required|date',
            'anotacoes'     => 'nullable|string|max:2000',
        ]);

        $data['consultor_id'] = Auth::id();
        $data['status']       = 'agendada';

        VisitaTecnica::create($data);

        return redirect()->route('consultor.visitas.index')->with('success', 'Visita agendada com sucesso.');
    }

    public function show(VisitaTecnica $visita): Response
    {
        $visita->load(['cliente', 'orcamento']);

        return Inertia::render('Consultor/Visitas/Show', [
            'visita' => $visita,
        ]);
    }

    public function edit(VisitaTecnica $visita): Response
    {
        return Inertia::render('Consultor/Visitas/Form', [
            'visita'     => $visita,
            'clientes'   => Cliente::where('consultor_id', Auth::id())->orderBy('nome')->get(['id', 'nome', 'razao_social', 'tipo_pessoa']),
            'orcamentos' => Orcamento::where('consultor_id', Auth::id())->whereIn('status', ['aprovado', 'assinado'])->latest()->get(['id', 'cliente_id', 'preco_total', 'created_at']),
        ]);
    }

    public function update(Request $request, VisitaTecnica $visita): RedirectResponse
    {
        $data = $request->validate([
            'data_agendada' => 'required|date',
            'status'        => 'required|in:agendada,realizada,cancelada',
            'anotacoes'     => 'nullable|string|max:2000',
        ]);

        $visita->update($data);

        return back()->with('success', 'Visita atualizada.');
    }

    public function destroy(VisitaTecnica $visita): RedirectResponse
    {
        $visita->delete();

        return redirect()->route('consultor.visitas.index')->with('success', 'Visita removida.');
    }
}
