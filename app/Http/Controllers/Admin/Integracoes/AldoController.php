<?php

namespace App\Http\Controllers\Admin\Integracoes;

use App\Http\Controllers\Controller;
use App\Models\Fornecedor;
use App\Models\IntegracaoHistorico;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Integração Aldo ainda não implementada: falta a especificação do feed
 * (URL, credenciais e formato do arquivo ZIP/XML). Enquanto isso a tela
 * mostra o status e o botão fica desabilitado.
 */
class AldoController extends Controller
{
    private const DISPONIVEL = false;

    public function index(): Response
    {
        $fornecedor = Fornecedor::where('nome', 'like', '%aldo%')->first();

        $ultimaIntegracao = IntegracaoHistorico::where('tipo', 'aldo')
            ->latest('iniciado_em')
            ->first();

        return Inertia::render('Admin/Integracoes/Aldo/Index', [
            'fornecedor' => $fornecedor,
            'ultima_integracao' => $ultimaIntegracao,
            'disponivel' => self::DISPONIVEL,
        ]);
    }

    public function integrar(): RedirectResponse
    {
        return back()->with('warning', 'Integração Aldo ainda não disponível: aguardando a especificação do feed do distribuidor.');
    }
}
