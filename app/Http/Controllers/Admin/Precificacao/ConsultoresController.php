<?php

namespace App\Http\Controllers\Admin\Precificacao;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConsultoresController extends Controller
{
    public function index(): Response
    {
        $consultores = User::where('tipo', 'consultor')
            ->where('status', true)
            ->withCount(['orcamentos as total_orcamentos', 'clientes as total_clientes'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'tipo', 'status', 'comissao_percentual']);

        return Inertia::render('Admin/Precificacao/Consultores/Index', [
            'consultores' => $consultores,
        ]);
    }

    public function update(Request $request, User $consultor): RedirectResponse
    {
        $data = $request->validate([
            'comissao_percentual' => 'required|numeric|min:0|max:100',
        ]);

        $consultor->update($data);

        return back()->with('success', 'Comissão do consultor atualizada.');
    }
}
