<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use App\Models\Orcamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContratosController extends Controller
{
    public function index(Request $request): Response
    {
        $contratos = Contrato::query()
            ->where('consultor_id', Auth::id())
            ->with(['orcamento.cliente'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Consultor/Contratos/Index', [
            'contratos' => $contratos,
            'filters'   => $request->only(['status']),
        ]);
    }

    public function show(Contrato $contrato): Response
    {
        $contrato->load(['orcamento.cliente']);

        return Inertia::render('Consultor/Contratos/Show', [
            'contrato' => $contrato,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'orcamento_id'          => 'required|exists:orcamentos,id',
            'nome_cliente'          => 'required|string|max:255',
            'documento_cliente'     => 'required|string|max:20',
            'endereco_instalacao'   => 'required|string|max:500',
            'potencia_kwp'          => 'required|numeric|min:0',
            'qtd_paineis'           => 'nullable|integer|min:0',
            'qtd_inversores'        => 'nullable|integer|min:0',
            'modelo_inversor'       => 'nullable|string|max:255',
            'consumo_mensal'        => 'nullable|numeric|min:0',
            'geracao_estimada'      => 'nullable|integer|min:0',
            'garantia_paineis'      => 'nullable|integer|min:0',
            'garantia_inversores'   => 'nullable|integer|min:0',
            'valor_total'           => 'required|numeric|min:0',
            'formas_pagamento'      => 'nullable|string',
            'clausulas_adicionais'  => 'nullable|string',
        ]);

        $data['consultor_id'] = Auth::id();
        $data['status']       = 'pendente';

        $contrato = Contrato::create($data);

        return redirect()->route('consultor.contratos.show', $contrato)->with('success', 'Contrato gerado com sucesso.');
    }

    public function pdf(Contrato $contrato): Response
    {
        return Inertia::render('Consultor/Contratos/Show', [
            'contrato' => $contrato->load(['orcamento.cliente']),
        ]);
    }
}
