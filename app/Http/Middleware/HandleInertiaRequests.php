<?php

namespace App\Http\Middleware;

use App\Services\Demo\ModoDemonstracao;
use App\Services\IdentidadeVisual;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->only('id', 'name', 'email', 'tipo', 'status'),
            ],
            // Nome, logos, favicon e cores definidos em Configurações → Identidade visual (também nas telas de login).
            'identidade' => fn () => IdentidadeVisual::atual(),
            // Modo demonstração (DEMO.md): perfis, caminhos liberados e visitante; null quando desligado.
            'demo' => fn () => app(ModoDemonstracao::class)->ativo() ? app(ModoDemonstracao::class)->propsCompartilhadas($request) : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
