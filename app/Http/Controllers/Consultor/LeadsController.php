<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultor\LeadUpdateRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LeadsController extends Controller
{
    public function index(Request $request): Response
    {
        $leads = Lead::query()
            ->where('consultor_id', Auth::id())
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nome', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('telefone', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Consultor/Leads/Index', [
            'leads' => $leads,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Lead $lead): Response
    {
        $this->authorize('view', $lead);

        return Inertia::render('Consultor/Leads/Show', [
            'lead' => $lead,
        ]);
    }

    public function update(LeadUpdateRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return back()->with('success', 'Lead atualizado.');
    }
}
