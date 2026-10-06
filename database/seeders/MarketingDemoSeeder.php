<?php

namespace Database\Seeders;

use App\Models\CidadeEstado;
use App\Models\Concessionaria;
use App\Models\Estrutura;
use App\Models\ParamDimensionamento;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\CatalogoDemo;
use Database\Seeders\Support\SimuladorComercial;
use Database\Seeders\Support\Sorteio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Base de demonstração para marketing (ver DEMO.md): uma empresa de energia solar fictícia
 * operando há 16 meses, com todas as telas cheias e trabalho pendente hoje.
 *
 *   DEMO_SEED_PERMITIDO=true php artisan migrate:fresh --force
 *   DEMO_SEED_PERMITIDO=true php artisan db:seed --class=MarketingDemoSeeder --force
 *
 * Só roda com DEMO_SEED_PERMITIDO=true (nunca no banco de desenvolvimento/produção) e uma
 * vez por banco. Determinístico: mesma semente, mesma base — com datas relativas a hoje.
 */
class MarketingDemoSeeder extends Seeder
{
    public const SEMENTE = 20261006;

    /** Volume de clientes e leads: 1 na base de marketing; os testes usam uma fração (mesmos fluxos, menos registros). */
    public static float $escala = 1.0;

    public function run(): void
    {
        if (! config('app.demo_seed_permitido')) {
            $this->command->error('MarketingDemoSeeder bloqueado: defina DEMO_SEED_PERMITIDO=true numa instalação de demonstração (banco próprio). Ver DEMO.md.');

            return;
        }

        if (User::where('email', 'like', '%@'.CatalogoDemo::DOMINIO_EQUIPE)->exists()) {
            $this->command->warn('A base de demonstração já foi criada neste banco. Para renovar: migrate:fresh e rode de novo (DEMO.md).');

            return;
        }

        $this->referencias();

        Sorteio::semente(self::SEMENTE);
        Model::unguard();
        $agora = CarbonImmutable::now();
        $inicio = $agora->subMonths(CatalogoDemo::MESES)->startOfMonth();

        try {
            DB::transaction(function () use ($inicio, $agora) {
                // O catálogo existe antes da operação começar (Auditoria: "sistema").
                Carbon::setTestNow($inicio->subDays(7)->setTime(10, 0));
                $this->call(DemoCatalogoSeeder::class);
                Carbon::setTestNow();

                $simulador = new SimuladorComercial($inicio, $agora, self::$escala);
                $simulador->executar();

                $this->call([DemoIntegracoesSeeder::class, DemoDadosFicticiosSeeder::class]);
                $this->resumo($simulador);
            });
        } finally {
            Carbon::setTestNow();
            Model::reguard();
        }
    }

    /** Tabelas de referência (cidades, irradiação, concessionárias, parâmetros) — sem elas os cálculos zeram. */
    private function referencias(): void
    {
        $this->call(array_values(array_filter([
            Estrutura::count() === 0 ? EstruturasSeeder::class : null,
            CategoriasProdutosSeeder::class, // idempotente (insertOrIgnore)
            ParamDimensionamento::count() === 0 ? ParamsDimensionamentoSeeder::class : null,
            ...(CidadeEstado::count() === 0 ? [CidadesEstadosSeeder::class, IrradiacaoSolarSeeder::class, IrradiacaoSolarComplementoSeeder::class, IrradiacaoSolarTodosSeeder::class] : []),
            Concessionaria::count() === 0 ? ConcessionariasSeeder::class : null,
        ])));
    }

    private function resumo(SimuladorComercial $simulador): void
    {
        $contar = fn (string $tabela, ?callable $filtro = null) => $filtro ? $filtro(DB::table($tabela))->count() : DB::table($tabela)->count();
        $eventos = $simulador->eventos();

        $this->command->newLine();
        $this->command->info('Base de demonstração criada ('.CatalogoDemo::MESES.' meses de operação).');
        $this->command->table(['Registro', 'Quantidade'], [
            ['Usuários (equipe)', $contar('users')],
            ['Clientes', $contar('clientes')],
            ['Leads', $contar('leads')],
            ['Orçamentos', $contar('orcamentos')],
            ['  ganhos (aprovado/instalando/finalizado)', $contar('orcamentos', fn ($q) => $q->whereIn('status', ['aprovado', 'instalando', 'finalizado']))],
            ['  perdidos', $contar('orcamentos', fn ($q) => $q->whereNotNull('perdido_em'))],
            ['  em negociação hoje', $contar('orcamentos', fn ($q) => $q->whereNull('perdido_em')->whereIn('status', ['novo', 'aprovacao_reprovada', 'aprovando']))],
            ['Contratos', $contar('contratos')],
            ['Visitas técnicas', $contar('visitas_tecnicas')],
            ['Propostas de serviço', $contar('proposta_servicos')],
            ['Linha do tempo (eventos)', $contar('orcamento_historicos')],
            ['Auditoria (registros)', $contar('activity_log')],
            ['Integrações (execuções)', $contar('integracao_historicos')],
            ['Kits no catálogo', $contar('kits')],
            ['Eventos simulados / pendentes no futuro', "{$eventos['executados']} / {$eventos['futuros']}"],
            ['Movimentos recusados pelo funil', $simulador->contagem['recusas_do_funil']],
        ]);

        $this->command->info('Logins (senha '.CatalogoDemo::SENHA_PADRAO.'):');
        foreach (CatalogoDemo::EQUIPE as $membro) {
            $this->command->line(sprintf('  %-10s %s@%s%s', $membro['tipo'], $membro['login'], CatalogoDemo::DOMINIO_EQUIPE, isset($membro['saida']) ? '  (desligado — login bloqueado)' : ''));
        }
    }
}
