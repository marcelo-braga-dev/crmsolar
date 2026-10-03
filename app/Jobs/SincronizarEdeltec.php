<?php

namespace App\Jobs;

use App\Services\Integracoes\Edeltec\EdeltecImportService;
use App\Services\Integracoes\Edeltec\EdeltecSincronizacaoEmAndamento;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sincronização Edeltec disparada pelo botão do Admin quando EDELTEC_SYNC_FILA=true.
 * O catálogo é grande demais para caber no tempo de uma requisição HTTP.
 */
class SincronizarEdeltec implements ShouldQueue
{
    use Queueable;

    /** Sem nova tentativa: o resultado (inclusive erro) fica no histórico e o Admin dispara de novo. */
    public int $tries = 1;

    public int $timeout = 1800;

    public function handle(): void
    {
        try {
            (new EdeltecImportService)->importar();
        } catch (EdeltecSincronizacaoEmAndamento) {
            Log::info('Edeltec (job): sincronização já em andamento — job ignorado.');
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::error('Edeltec (job): '.$e?->getMessage());
    }
}
