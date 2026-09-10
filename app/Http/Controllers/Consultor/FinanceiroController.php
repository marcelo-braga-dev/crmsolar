<?php

namespace App\Http\Controllers\Consultor;

use App\Http\Controllers\Controller;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FinanceiroController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();

        $orcamentosAprovados = Orcamento::where('consultor_id', $userId)
            ->whereIn('status', ['assinado', 'instalando', 'finalizado'])
            ->with(['cliente'])
            ->latest()
            ->get();

        $totalComissoes = OrcamentoItem::whereHas('orcamento', fn ($q) => $q
            ->where('consultor_id', $userId)
            ->whereIn('status', ['assinado', 'instalando', 'finalizado'])
        )->sum(\Illuminate\Support\Facades\DB::raw('preco_venda_total * comissao_percentual / 100'));

        return Inertia::render('Consultor/Financeiro/Index', [
            'orcamentos'      => $orcamentosAprovados,
            'total_comissoes' => $totalComissoes,
        ]);
    }
}
