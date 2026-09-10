<?php

namespace App\Http\Controllers\Admin\Precificacao;

use App\Http\Controllers\Controller;
use App\Models\Fornecedor;
use App\Models\MargemFornecedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FornecedoresController extends Controller
{
    public function index(): Response
    {
        $fornecedores = Fornecedor::with('margemPrecificacao')
            ->orderBy('nome')
            ->get()
            ->map(fn ($f) => [
                'id'     => $f->id,
                'nome'   => $f->nome,
                'ativo'  => $f->ativo,
                'margem' => $f->margemPrecificacao ? (float) $f->margemPrecificacao->margem : 0.0,
            ]);

        return Inertia::render('Admin/Precificacao/Fornecedores/Index', [
            'fornecedores' => $fornecedores,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fornecedor_id' => 'required|integer|exists:fornecedores,id',
            'margem'        => 'required|numeric|min:0|max:100',
        ]);

        MargemFornecedor::updateOrCreate(
            ['fornecedor_id' => $data['fornecedor_id']],
            ['margem' => $data['margem']]
        );

        return back()->with('success', 'Margem atualizada.');
    }
}
