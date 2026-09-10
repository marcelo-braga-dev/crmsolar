<?php

namespace App\Http\Controllers\Admin\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\OrcamentoItem;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComissoesController extends Controller
{
    public function index(Request $request): Response
    {
        $vendedores = User::whereIn('tipo', ['vendedor', 'admin_vendedor'])
            ->orderBy('name')
            ->get(['id', 'name', 'comissao_percentual']);

        $comissoes = OrcamentoItem::query()
            ->with(['orcamento.cliente', 'orcamento.consultor'])
            ->whereHas('orcamento', fn ($q) => $q->whereIn('status', ['assinado', 'instalando', 'finalizado']))
            ->when($request->consultor_id, fn ($q, $id) => $q->whereHas('orcamento', fn ($q2) => $q2->where('consultor_id', $id)))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Financeiro/Comissoes/Index', [
            'comissoes'  => $comissoes,
            'vendedores' => $vendedores,
            'filters'    => $request->only(['consultor_id']),
        ]);
    }
}
