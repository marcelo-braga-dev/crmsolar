<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeadsController extends Controller
{
    public function index(Request $request): Response
    {
        $leads = Lead::query()
            ->with('consultor:id,name')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nome', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('telefone', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->consultor_id, fn ($q, $id) => $q->where('consultor_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Leads/Index', [
            'leads' => $leads,
            'filters' => $request->only(['search', 'status', 'consultor_id']),
            'consultores' => User::where('tipo', '!=', 'admin')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function show(Lead $lead): Response
    {
        $lead->load('consultor:id,name');

        return Inertia::render('Admin/Leads/Show', [
            'lead' => $lead,
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:novo,contatado,encaminhado,convertido,perdido',
            'consultor_id' => ['nullable', Rule::exists('users', 'id')->where('tipo', 'consultor')],
            'anotacoes' => 'nullable|string',
        ]);

        $lead->update($data);

        return back()->with('success', 'Lead atualizado.');
    }
}
