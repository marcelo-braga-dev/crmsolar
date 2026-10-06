<?php

use App\Console\Commands\IntegracaoEdeltecCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Na demonstração (DEMO_MODE) nenhuma rotina que importa, sincroniza ou envia algo roda.
if (! config('demo.enabled')) {
    // Integração Edeltec — diariamente às 04h00
    Schedule::command(IntegracaoEdeltecCommand::class)
        ->dailyAt('04:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->emailOutputOnFailure(config('mail.admin_email', ''))
        ->appendOutputTo(storage_path('logs/edeltec.log'));
}
