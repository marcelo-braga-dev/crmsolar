<?php

namespace App\Console\Commands;

use App\Services\Integracoes\Edeltec\EdeltecImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IntegracaoEdeltecCommand extends Command
{
    protected $signature = 'app:integracao-edeltec {--force : Força execução mesmo fora do horário agendado}';

    protected $description = 'Sincroniza o catálogo de kits com a Edeltec Solar (roda diariamente às 04h)';

    public function handle(): int
    {
        $this->info('[Edeltec] Iniciando integração...');
        $inicio = microtime(true);

        try {
            $historico = (new EdeltecImportService)->importar();

            $duracao = round(microtime(true) - $inicio, 1);

            $this->info("[Edeltec] Concluído em {$duracao}s");
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Status',       $historico->status],
                    ['Importados',   $historico->itens_importados],
                    ['Atualizados',  $historico->itens_atualizados],
                    ['Desativados',  $historico->itens_desativados],
                    ['Alertas',      $historico->alertas ? substr($historico->alertas, 0, 200).'...' : 'Nenhum'],
                    ['Duração',      "{$duracao}s"],
                ]
            );

            if ($historico->alertas) {
                $this->warn('[Edeltec] Há alertas — verifique o histórico no painel.');
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('[Edeltec] FALHA CRÍTICA: '.$e->getMessage());
            Log::error('Edeltec Command: falha', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }
}
