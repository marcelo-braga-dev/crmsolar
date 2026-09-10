<?php

namespace App\Http\Controllers\Admin\Precificacao;

use App\Http\Controllers\Controller;
use App\Models\Estrutura;
use App\Models\MargemEstrutura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EstruturasController extends Controller
{
    public function index(): Response
    {
        $estruturas = Estrutura::with('margemPrecificacao')
            ->orderBy('nome')
            ->get()
            ->map(fn ($e) => [
                'id'     => $e->id,
                'nome'   => $e->nome,
                'ativo'  => $e->ativo,
                'margem' => $e->margemPrecificacao ? (float) $e->margemPrecificacao->margem : 0.0,
            ]);

        return Inertia::render('Admin/Precificacao/Estruturas/Index', [
            'estruturas' => $estruturas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'estrutura_id' => 'required|integer|exists:estruturas,id',
            'margem'       => 'required|numeric|min:0|max:100',
        ]);

        MargemEstrutura::updateOrCreate(
            ['estrutura_id' => $data['estrutura_id']],
            ['margem' => $data['margem']]
        );

        return back()->with('success', 'Margem atualizada.');
    }
}
