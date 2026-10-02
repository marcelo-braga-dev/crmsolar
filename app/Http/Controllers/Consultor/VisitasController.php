<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultor\VisitaRequest;
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
            'clientes' => Cliente::where('consultor_id', Auth::id())->orderBy('nome')->get(['id', 'nome', 'razao_social', 'tipo_pessoa']),
            'orcamentos' => Orcamento::where('consultor_id', Auth::id())->whereIn('status', ['aprovado', 'instalando'])->latest()->get(['id', 'cliente_id', 'preco_total', 'created_at']),
        ]);
    }

    public function store(VisitaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['consultor_id'] = Auth::id();
        $data['status'] = 'agendada';

        VisitaTecnica::create($data);

        return redirect()->route('consultor.visitas.index')->with('success', 'Visita agendada com sucesso.');
    }

    public function show(VisitaTecnica $visita): Response
    {
        $this->authorize('view', $visita);

        $visita->load(['cliente', 'orcamento']);

        return Inertia::render('Consultor/Visitas/Show', [
            'visita' => $visita,
        ]);
    }

    public function edit(VisitaTecnica $visita): Response
    {
        $this->authorize('update', $visita);

        return Inertia::render('Consultor/Visitas/Form', [
            'visita' => $visita,
            'clientes' => Cliente::where('consultor_id', Auth::id())->orderBy('nome')->get(['id', 'nome', 'razao_social', 'tipo_pessoa']),
            'orcamentos' => Orcamento::where('consultor_id', Auth::id())->whereIn('status', ['aprovado', 'instalando'])->latest()->get(['id', 'cliente_id', 'preco_total', 'created_at']),
        ]);
    }

    public function update(VisitaRequest $request, VisitaTecnica $visita): RedirectResponse
    {
        $visita->update($request->validated());

        return back()->with('success', 'Visita atualizada.');
    }

    public function destroy(VisitaTecnica $visita): RedirectResponse
    {
        $this->authorize('delete', $visita);

        $visita->delete();

        return redirect()->route('consultor.visitas.index')->with('success', 'Visita removida.');
    }
}
