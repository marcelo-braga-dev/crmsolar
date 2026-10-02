<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configureRateLimiting();
    }

    /**
     * Limitadores para a API pública (rotas sem autenticação em routes/web.php).
     */
    private function configureRateLimiting(): void
    {
        // Formulário público de captura de lead: generoso o bastante para um
        // usuário reenviar após erro, apertado o bastante para travar flood.
        RateLimiter::for('leads-submit', fn ($request) => Limit::perMinute(5)->by($request->ip()));

        // Autocomplete de estado/cidade/CEP durante o preenchimento de um formulário.
        RateLimiter::for('public-lookup', fn ($request) => Limit::perMinute(60)->by($request->ip()));

        // Visualização de proposta compartilhada via token.
        RateLimiter::for('orcamento-publico', fn ($request) => Limit::perMinute(30)->by($request->ip()));
    }
}
