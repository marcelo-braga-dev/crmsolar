<?php

namespace Tests\Feature\Demo;

use App\Models\Cliente;
use App\Models\Config;
use App\Models\Contrato;
use App\Models\Fornecedor;
use App\Models\IntegracaoHistorico;
use App\Models\Lead;
use App\Models\Orcamento;
use App\Models\OrcamentoHistorico;
use App\Models\PropostaServico;
use App\Models\User;
use App\Models\VisitaTecnica;
use Database\Seeders\DemoDadosFicticiosSeeder;
use Database\Seeders\MarketingDemoSeeder;
use Database\Seeders\Support\CatalogoDemo;
use Database\Seeders\Support\Ficticio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Base de demonstração para marketing (DEMO.md). A simulação completa leva dezenas de
 * segundos, então roda uma vez só e o mesmo teste confere coerência, telas e marcação.
 */
class MarketingDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private const DOMINIO = '@'.CatalogoDemo::DOMINIO_EQUIPE;

    protected function setUp(): void
    {
        parent::setUp();
        // Mesmos 16 meses e os mesmos fluxos da base de marketing, com ~1/3 do volume (a completa leva ~1 min).
        MarketingDemoSeeder::$escala = 0.35;
    }

    protected function tearDown(): void
    {
        MarketingDemoSeeder::$escala = 1.0;
        parent::tearDown();
    }

    public function test_sem_a_chave_de_ambiente_nao_popula_nada(): void
    {
        config(['app.demo_seed_permitido' => false]);

        $this->seed(MarketingDemoSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Orcamento::count());
    }

    public function test_marcacao_funciona_em_banco_que_ja_tem_fornecedores(): void
    {
        // O fornecedor de id maior guarda o CNPJ fictício que o de id menor vai receber (índice único).
        $a = Fornecedor::create(['nome' => 'Distribuidora A', 'cnpj' => '11.111.111/0001-11']);
        $b = Fornecedor::create(['nome' => 'Distribuidora B', 'cnpj' => Ficticio::cnpjFornecedor($a->id)]);

        $this->seed(DemoDadosFicticiosSeeder::class);
        $this->seed(DemoDadosFicticiosSeeder::class); // idempotente

        $this->assertSame(Ficticio::cnpjFornecedor($a->id), $a->fresh()->cnpj);
        $this->assertSame(Ficticio::cnpjFornecedor($b->id), $b->fresh()->cnpj);
        $this->assertSame('Distribuidora B (fictícia)', $b->fresh()->nome);
    }

    public function test_pdfs_so_trazem_o_aviso_de_demonstracao_na_base_de_demonstracao(): void
    {
        $this->assertStringNotContainsString('demonstração', view('pdf._aviso_demo')->render());

        Config::set('demo_aviso', 'Documento de demonstração com dados fictícios', 'geral');

        $this->assertStringContainsString('Documento de demonstração com dados fictícios', view('pdf._aviso_demo')->render());
        foreach (['pdf/orcamento.blade.php', 'pdf/contrato.blade.php'] as $arquivo) {
            $this->assertStringContainsString("@include('pdf._aviso_demo')", file_get_contents(resource_path("views/{$arquivo}")));
        }
    }

    public function test_base_de_demonstracao_completa_coerente_e_marcada_como_ficticia(): void
    {
        config(['app.demo_seed_permitido' => true]);
        $this->seed(MarketingDemoSeeder::class);

        $this->volumesEmTodasAsEtapas();
        $this->nadaNoFuturoEFluxoCoerente();
        $this->dadosMarcadosComoFicticios();
        $this->telasCheiasEmTodosOsPerfis();

        // Idempotente: rodar de novo não duplica a base nem a marcação.
        $antes = [User::count(), Orcamento::count(), Cliente::where('nome', 'like', '%(fictício) (fictício)%')->count()];
        $this->seed(MarketingDemoSeeder::class);
        $this->seed(DemoDadosFicticiosSeeder::class);
        $this->assertSame($antes, [User::count(), Orcamento::count(), Cliente::where('nome', 'like', '%(fictício) (fictício)%')->count()]);
        $this->assertSame(0, $antes[2]);
    }

    private function volumesEmTodasAsEtapas(): void
    {
        $this->assertSame(count(CatalogoDemo::EQUIPE), User::where('email', 'like', '%'.self::DOMINIO)->count());
        $this->assertGreaterThan(120, Cliente::count());
        $this->assertGreaterThan(120, Lead::count());
        $this->assertGreaterThan(120, Orcamento::count());

        // Funil inteiro: ganhos em todas as fases do pós-venda, perdidos, em aprovação e em negociação.
        foreach (['finalizado', 'instalando', 'aprovado'] as $status) {
            $this->assertGreaterThan(0, Orcamento::where('status', $status)->count(), "nenhum orçamento {$status}");
        }
        $this->assertGreaterThan(30, Orcamento::whereNotNull('perdido_em')->count());
        $this->assertGreaterThan(5, Orcamento::whereNull('perdido_em')->whereIn('status', Orcamento::STATUS_EM_NEGOCIACAO)->whereNotNull('funil_etapa_id')->count());
        $this->assertGreaterThan(0, Orcamento::where('tentativas_reativacao', '>', 0)->count(), 'nenhuma reativação');
        $this->assertGreaterThan(0, OrcamentoHistorico::where('status', 'aprovacao_reprovada')->count(), 'nenhuma reprovação');

        // Casos-problema de propósito.
        $this->assertTrue(Contrato::where('status', 'assinado')->count() > 20);
        $this->assertTrue(IntegracaoHistorico::where('status', 'erro')->exists(), 'sem falha de integração');
        // Consultor desligado: login bloqueado e nenhuma negociação em andamento ficou com ele.
        $desligado = User::where('email', 'diego.martins'.self::DOMINIO)->where('status', false)->firstOrFail();
        $this->assertGreaterThan(0, Orcamento::where('consultor_id', $desligado->id)->count());
        $this->assertSame(0, Orcamento::where('consultor_id', $desligado->id)->whereNull('perdido_em')->whereIn('status', Orcamento::STATUS_EM_NEGOCIACAO)->count());

        foreach (['contratos', 'visitas_tecnicas', 'proposta_servicos', 'orcamento_vistorias', 'orcamento_aprovacoes', 'integracao_historicos', 'bancos', 'kits', 'produtos'] as $tabela) {
            $this->assertGreaterThan(0, DB::table($tabela)->count(), "tabela {$tabela} vazia");
        }
        foreach (['aceita', 'recusada'] as $status) {
            $this->assertTrue(PropostaServico::where('status', $status)->exists(), "nenhuma proposta {$status}");
        }
        foreach (['realizada', 'cancelada'] as $status) {
            $this->assertTrue(VisitaTecnica::where('status', $status)->exists(), "nenhuma visita {$status}");
        }
        foreach (['convertido', 'perdido'] as $status) {
            $this->assertTrue(Lead::where('status', $status)->exists(), "nenhum lead {$status}");
        }

        // Crescimento: o mês fechado mais recente tem mais orçamentos que o primeiro trimestre.
        $inicio = now()->subMonths(CatalogoDemo::MESES)->startOfMonth();
        $primeiros = Orcamento::whereBetween('created_at', [$inicio, $inicio->copy()->addMonths(3)])->count();
        $ultimoMes = Orcamento::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->startOfMonth()])->count();
        $this->assertGreaterThan($primeiros, $ultimoMes);
    }

    private function nadaNoFuturoEFluxoCoerente(): void
    {
        $agora = now()->addMinute();
        foreach (['clientes', 'leads', 'orcamentos', 'orcamento_historicos', 'contratos', 'proposta_servicos', 'activity_log', 'integracao_historicos'] as $tabela) {
            $this->assertSame(0, DB::table($tabela)->where('created_at', '>', $agora)->count(), "{$tabela} com data no futuro");
        }
        $this->assertSame(0, VisitaTecnica::where('status', 'realizada')->where('data_agendada', '>', $agora)->count());

        // Venda só existe depois de aprovação; contrato só de orçamento aprovado, com o valor dele.
        $ganho = Orcamento::where('status', 'finalizado')->with(['historicos', 'contrato'])->first();
        $this->assertNotNull($ganho->contrato);
        $this->assertEquals((float) $ganho->preco_total, (float) $ganho->contrato->valor_total);
        $this->assertTrue($ganho->historicos->contains('status', 'aprovado'));
        $this->assertTrue($ganho->contrato->created_at->greaterThanOrEqualTo($ganho->historicos->firstWhere('status', 'aprovado')->created_at));
        $this->assertSame(0, Contrato::whereHas('orcamento', fn ($q) => $q->whereIn('status', Orcamento::STATUS_PRE_APROVACAO))->count());

        // Orçamentos com a fórmula do sistema: preço = custo × (1 + margens) e geração calculada.
        $this->assertSame(0, Orcamento::where('preco_total', '<=', 0)->orWhere('geracao_estimada', '<=', 0)->count());
    }

    private function dadosMarcadosComoFicticios(): void
    {
        $this->assertSame(0, Cliente::whereRaw("coalesce(nome, razao_social) not like '%(fictíci%'")->count());
        $this->assertSame(0, Lead::where('nome', 'not like', '%(fictíci%')->count());
        $this->assertSame(0, User::where('name', 'not like', '%(fictíci%')->count());
        $this->assertSame(0, Cliente::where(fn ($q) => $q->where('cpf', 'not like', '000.%')->orWhere('cnpj', 'not like', '00.000.%')->orWhere('celular', 'not like', '(20) 0000-%'))->count());
        $this->assertSame(0, Cliente::where('email', 'not like', '%@'.CatalogoDemo::DOMINIO_CLIENTES)->count());
        $this->assertSame('Mariana Costa (fictícia)', User::where('email', 'mariana.costa'.self::DOMINIO)->value('name'));

        $contrato = Contrato::with('orcamento.cliente')->first();
        $this->assertSame($contrato->orcamento->cliente->nome_display, $contrato->nome_cliente);
        $lead = Lead::where('status', 'convertido')->whereNotNull('anotacoes')->first();
        $this->assertMatchesRegularExpression('/por .+ \(fictíci[oa]\)\.$/u', $lead->anotacoes); // nome copiado no texto também foi marcado
        $nomeNaAuditoria = Activity::where('subject_type', Cliente::class)->where('event', 'created')->first()->attribute_changes['attributes']['nome']
            ?? Activity::where('subject_type', Cliente::class)->where('event', 'created')->first()->properties['attributes']['nome'] ?? '(fictício)';
        $this->assertStringContainsString('(fictíci', (string) $nomeNaAuditoria);

        $this->assertStringContainsString('fictícia', Config::get('empresa_nome'));
        $this->assertNotEmpty(Config::get('demo_aviso'));
    }

    private function telasCheiasEmTodosOsPerfis(): void
    {
        $admin = User::where('email', 'admin'.self::DOMINIO)->first();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertInertia(fn ($page) => $page
            ->where('stats.clientes_total', fn ($v) => $v > 120)
            ->where('stats.valor_aprovado_total', fn ($v) => $v > 0)
            ->where('stats.consultores_ativos', count(array_filter(CatalogoDemo::EQUIPE, fn ($m) => $m['tipo'] === 'consultor' && ! isset($m['saida']))))
            ->has('evolucao', 6)
            ->where('evolucao.4.qtd', fn ($v) => $v > 0)
            ->has('recentes', 8));
        $this->actingAs($admin)->get(route('admin.funil.index'))->assertInertia(fn ($page) => $page->where('resumo.abertos', fn ($v) => $v > 5));
        $this->actingAs($admin)->get(route('admin.configuracoes.auditoria'))->assertInertia(fn ($page) => $page->where('atividades.total', fn ($v) => $v > 1000));
        $this->actingAs($admin)->get(route('admin.financeiro.comissoes.index'))->assertInertia(fn ($page) => $page->where('comissoes.total', fn ($v) => $v > 20));
        $this->actingAs($admin)->get(route('admin.integracoes.historico'))->assertInertia(fn ($page) => $page->where('historicos.total', fn ($v) => $v > 20));

        // Todo consultor ativo tem carteira (a recém-contratada também, menor).
        foreach (User::where('tipo', 'consultor')->where('status', true)->get() as $consultor) {
            $this->actingAs($consultor)->get(route('consultor.dashboard'))
                ->assertInertia(fn ($page) => $page->where('stats.clientes', fn ($v) => $v > 0)->has('recentes'));
        }

        $principal = User::where('email', 'consultor'.self::DOMINIO)->first();
        $this->actingAs($principal)->get(route('consultor.funil.index'))->assertInertia(fn ($page) => $page->where('resumo.abertos', fn ($v) => $v > 0));
        $this->actingAs($principal)->get(route('consultor.financeiro'))->assertInertia(fn ($page) => $page->where('total_comissoes', fn ($v) => $v > 0));
        $this->actingAs($principal)->get(route('consultor.visitas.index'))->assertInertia(fn ($page) => $page->where('visitas.total', fn ($v) => $v > 0));
        $this->actingAs($principal)->get(route('consultor.contratos.index'))->assertInertia(fn ($page) => $page->where('contratos.total', fn ($v) => $v > 0));
    }
}
