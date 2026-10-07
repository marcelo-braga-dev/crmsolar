<?php

namespace Database\Seeders;

use App\Models\Fornecedor;
use App\Models\IntegracaoHistorico;
use App\Models\Kit;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\CatalogoDemo;
use Database\Seeders\Support\Sorteio;
use Illuminate\Database\Seeder;

/**
 * Histórico de integrações da demo, no formato que o EdeltecImportService grava: carga
 * inicial por planilha, sincronização Edeltec diária às 04h (semanal nos meses antigos),
 * alguns dias sem execução, avisos de SKUs ignorados e uma falha ocasional. Nada chama
 * a API real.
 */
class DemoIntegracoesSeeder extends Seeder
{
    public function run(): void
    {
        if (IntegracaoHistorico::where('detalhes->demo', true)->exists()) {
            return;
        }

        $edeltec = Fornecedor::daIntegracao()->first();
        $solaris = Fornecedor::where('nome', 'like', 'Solaris%')->first();
        $luminar = Fornecedor::where('nome', 'like', 'Luminar%')->first();
        if (! $edeltec) {
            return;
        }

        $agora = CarbonImmutable::now();
        $inicio = $agora->subMonths(CatalogoDemo::MESES)->startOfMonth();
        $kitsEdeltec = Kit::where('fornecedor_id', $edeltec->id)->count();
        $skus = Kit::where('fornecedor_id', $edeltec->id)->orderBy('id')->limit(40)->pluck('sku')->all();

        // Cargas iniciais dos outros fornecedores (planilha e cadastro manual).
        foreach ([[$luminar, 'excel', $inicio->subDays(4)], [$solaris, 'excel', $inicio->subDays(3)], [$solaris, 'manual', $inicio->addMonths(5)->addDays(11)]] as [$fornecedor, $tipo, $quando]) {
            if (! $fornecedor) {
                continue;
            }
            $this->registrar($fornecedor->id, $tipo, 'concluido', $quando->setTime(14, 10), Sorteio::entre(240, 900), [
                'importados' => $tipo === 'manual' ? Sorteio::entre(3, 12) : Kit::where('fornecedor_id', $fornecedor->id)->count(),
                'atualizados' => $tipo === 'manual' ? Sorteio::entre(10, 40) : 0, 'desativados' => 0,
                'alertas' => $tipo === 'excel' ? 'Linha 312: potência em branco — ignorada.' : null,
            ]);
        }

        // Edeltec: semanal no começo, diária nos últimos 45 dias (04h de Brasília = 07h UTC).
        for ($dia = $inicio; $dia->lessThanOrEqualTo($agora); $dia = $dia->addDay()) {
            $recente = $dia->greaterThan($agora->subDays(45));
            // Falha ocasional de propósito (há 9 dias, e uma mais antiga), sempre presente para a tela de histórico.
            $diaDeFalha = in_array((int) $dia->startOfDay()->diffInDays($agora->startOfDay()), [9, 61], true);
            if (! $diaDeFalha && ((! $recente && ! $dia->isMonday()) || ($recente && Sorteio::chance(0.05)))) {
                continue; // semanal antes; nos últimos dias, raras execuções puladas (manutenção do servidor)
            }

            $quando = $dia->setTime(7, Sorteio::entre(0, 2), Sorteio::entre(0, 59));
            if ($quando->greaterThan($agora)) {
                break;
            }

            if ($diaDeFalha) {
                $this->registrar($edeltec->id, 'edeltec', 'erro', $quando, Sorteio::entre(30, 65), [
                    'alertas' => 'ERRO CRÍTICO: cURL error 28: Operation timed out after 30001 milliseconds (API da distribuidora — demonstração)',
                ]);

                continue;
            }

            $alertas = Sorteio::chance(0.18) ? implode(PHP_EOL, array_map(
                fn ($sku) => "SKU {$sku}: preço zerado na API — mantido o preço anterior.",
                array_slice($skus, Sorteio::entre(0, 30), Sorteio::entre(1, 3)),
            )) : null;

            $this->registrar($edeltec->id, 'edeltec', 'concluido', $quando, Sorteio::entre(70, 240), [
                'importados' => $kitsEdeltec + Sorteio::entre(0, 6),
                'atualizados' => Sorteio::entre(8, (int) max(9, $kitsEdeltec * 0.35)),
                'desativados' => Sorteio::chance(0.2) ? Sorteio::entre(1, 4) : 0,
                'alertas' => $alertas,
            ]);
        }
    }

    /** @param  array{importados?: int, atualizados?: int, desativados?: int, alertas?: string|null}  $dados */
    private function registrar(int $fornecedorId, string $tipo, string $status, CarbonImmutable $inicio, int $segundos, array $dados): void
    {
        $fim = $inicio->addSeconds($segundos);

        $historico = new IntegracaoHistorico([
            'fornecedor_id' => $fornecedorId,
            'tipo' => $tipo,
            'status' => $status,
            'alertas' => $dados['alertas'] ?? null,
            'itens_importados' => $dados['importados'] ?? 0,
            'itens_atualizados' => $dados['atualizados'] ?? 0,
            'itens_desativados' => $dados['desativados'] ?? 0,
            'detalhes' => ['demo' => true, 'total_paginas' => $status === 'erro' ? 0 : 1],
            'iniciado_em' => $inicio,
            'finalizado_em' => $fim,
        ]);
        $historico->created_at = $inicio;
        $historico->updated_at = $fim;
        $historico->save();
    }
}
