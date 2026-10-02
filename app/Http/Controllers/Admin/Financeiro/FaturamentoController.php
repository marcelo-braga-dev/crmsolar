<?php

namespace App\Http\Controllers\Admin\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Orcamento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaturamentoController extends Controller
{
    public function index(Request $request): Response
    {
        $orcamentos = Orcamento::query()
            ->with(['cliente', 'consultor'])
            ->whereIn('status', ['aprovado', 'instalando', 'finalizado'])
            ->when($request->mes, fn ($q, $mes) => $q->whereMonth('created_at', $mes))
            ->when($request->ano, fn ($q, $ano) => $q->whereYear('created_at', $ano))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $total = Orcamento::whereIn('status', ['aprovado', 'instalando', 'finalizado'])->sum('preco_total');

        return Inertia::render('Admin/Financeiro/Faturamento/Index', [
            'orcamentos' => $orcamentos,
            'total'      => $total,
            'filters'    => $request->only(['mes', 'ano']),
        ]);
    }
}
