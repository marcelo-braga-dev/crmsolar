<?php

namespace App\Http\Controllers\Admin\Integracoes;

use App\Http\Controllers\Controller;
use App\Models\Fornecedor;
use App\Models\IntegracaoHistorico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AldoController extends Controller
{
    public function index(): Response
    {
        $fornecedor = Fornecedor::where('nome', 'like', '%aldo%')->first();

        $ultimaIntegracao = IntegracaoHistorico::where('tipo', 'aldo')
            ->latest('iniciado_em')
            ->first();

        return Inertia::render('Admin/Integracoes/Aldo/Index', [
            'fornecedor'        => $fornecedor,
            'ultima_integracao' => $ultimaIntegracao,
        ]);
    }

    public function integrar(Request $request): RedirectResponse
    {
        return back()->with('info', 'Integração Aldo ainda não implementada nesta versão.');
    }
}
