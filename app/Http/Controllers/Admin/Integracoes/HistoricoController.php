<?php

namespace App\Http\Controllers\Admin\Integracoes;

use App\Http\Controllers\Controller;
use App\Models\IntegracaoHistorico;
use App\Services\Integracoes\NomeDistribuidora;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoricoController extends Controller
{
    public function index(Request $request, NomeDistribuidora $nome): Response
    {
        // O banco guarda "edeltec"; para o navegador o tipo é sempre "distribuidora" (nome confidencial na demonstração).
        $tipo = $request->tipo === NomeDistribuidora::TIPO_PUBLICO ? 'edeltec' : $request->tipo;

        $historicos = IntegracaoHistorico::with('fornecedor:id,nome')
            ->when($tipo, fn ($q, $t) => $q->where('tipo', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('iniciado_em')
            ->paginate(30)
            ->withQueryString()
            ->through(function (IntegracaoHistorico $h) {
                if ($h->tipo === 'edeltec') {
                    $h->tipo = NomeDistribuidora::TIPO_PUBLICO;
                }

                return $h;
            });

        return Inertia::render('Admin/Integracoes/Historico/Index', [
            'distribuidora' => $nome->exibido(),
            'historicos' => $historicos,
            'filters' => $request->only(['tipo', 'status']),
        ]);
    }
}
