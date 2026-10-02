<?php

namespace App\Http\Controllers\Admin\Configuracoes;

use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SistemaController extends Controller
{
    public function index(): Response
    {
        $configs = Config::whereIn('grupo', ['empresa', 'proposta', 'geral'])
            ->orderBy('grupo')
            ->orderBy('chave')
            ->get()
            ->groupBy('grupo');

        return Inertia::render('Admin/Configuracoes/Sistema/Index', [
            'configs' => $configs,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'configs' => 'required|array',
            'configs.*.chave' => 'required|string|max:100',
            'configs.*.valor' => 'nullable|string|max:1000',
            'configs.*.grupo' => 'required|string|max:50',
        ]);

        foreach ($data['configs'] as $item) {
            Config::set($item['chave'], $item['valor'] ?? '', $item['grupo']);
        }

        return back()->with('success', 'Configurações do sistema salvas.');
    }
}
