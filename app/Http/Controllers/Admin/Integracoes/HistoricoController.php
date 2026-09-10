<?php

namespace App\Http\Controllers\Admin\Integracoes;

use App\Http\Controllers\Controller;
use App\Models\IntegracaoHistorico;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoricoController extends Controller
{
    public function index(Request $request): Response
    {
        $historicos = IntegracaoHistorico::with('fornecedor:id,nome')
            ->when($request->tipo, fn ($q, $t) => $q->where('tipo', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('iniciado_em')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Integracoes/Historico/Index', [
            'historicos' => $historicos,
            'filters'    => $request->only(['tipo', 'status']),
        ]);
    }
}
