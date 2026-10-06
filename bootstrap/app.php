<?php

use App\Http\Middleware\BloqueiaEscritaNaDemonstracao;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsConsultor;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureUserIsActive::class,
            HandleInertiaRequests::class,
            // Modo demonstração: depois do Inertia (o aviso vai como flash para a página). Desligado, só repassa.
            BloqueiaEscritaNaDemonstracao::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Formulário de captura de lead é postado de sites externos, sem token CSRF.
        $middleware->validateCsrfTokens(except: ['api/leads']);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'consultor' => EnsureUserIsConsultor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Exclusão barrada por chave estrangeira: volta para a tela com aviso em vez de erro 500.
        // Restrito a DELETE para não mascarar outros erros de integridade.
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->isMethod('delete') && ! $request->expectsJson() && str_starts_with((string) $e->getCode(), '23')) {
                return back()->with('error', 'Não é possível excluir: o registro está vinculado a outros dados.');
            }
        });

        // Sem efeito até SENTRY_LARAVEL_DSN ser definido no .env.
        Integration::handles($exceptions);
    })->create();
