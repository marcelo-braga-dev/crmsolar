<?php

namespace App\Console\Commands;

use App\Services\Demo\ExportacaoVisitantes;
use Illuminate\Console\Command;

/** Visitantes do modo demonstração: tabela no terminal ou CSV (DEMO.md). */
class DemoVisitantesCommand extends Command
{
    protected $signature = 'demo:visitantes {arquivo? : caminho do CSV a gravar} {--desde= : só quem acessou a partir de AAAA-MM-DD}';

    protected $description = 'Lista ou exporta em CSV os visitantes do modo demonstração';

    public function handle(): int
    {
        $desde = $this->option('desde');
        if ($desde && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $this->error('Use --desde=AAAA-MM-DD.');

            return self::FAILURE;
        }

        if ($arquivo = $this->argument('arquivo')) {
            $saida = fopen($arquivo, 'w');
            ExportacaoVisitantes::escrever($saida, $desde);
            fclose($saida);
            $this->info("CSV gravado em {$arquivo} (".ExportacaoVisitantes::consulta($desde)->count().' visitantes).');

            return self::SUCCESS;
        }

        $linhas = ExportacaoVisitantes::consulta($desde)->get()->map(fn ($v) => ExportacaoVisitantes::linha($v))->all();
        $this->table(ExportacaoVisitantes::cabecalho(), $linhas);
        $this->line(count($linhas).' visitante(s).');

        return self::SUCCESS;
    }
}
