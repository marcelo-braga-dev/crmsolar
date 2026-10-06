<?php

namespace Tests\Feature\Demo;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Banco;
use App\Models\DemoVisitante;
use App\Models\User;
use App\Services\Demo\ModoDemonstracao;
use App\Services\IdentidadeVisual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/** Modo demonstração (DEMO.md): acesso sem senha, troca de perfil, somente leitura e visitantes. */
class ModoDemonstracaoTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    private User $admin;

    private User $consultor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->admin(['email' => 'admin@crmsolar.demo']);
        $this->consultor = $this->consultor(['email' => 'consultor@crmsolar.demo']);
    }

    private function ligar(array $extra = []): void
    {
        config(['demo.enabled' => true, 'demo.leads_token' => 'segredo-123', 'demo.initial_role' => 'admin'] + $extra);
    }

    private function acessar(array $dados = []): TestResponse
    {
        return $this->post(route('demo.acesso'), $dados + [
            'nome' => 'Visitante Teste', 'email' => '  Visitante@Empresa.COM ', 'telefone' => '(11) 98765-4321',
            'empresa' => 'Solar Ltda', 'aceite' => true, 'utm_source' => 'linkedin', 'utm_campaign' => 'lancamento',
        ]);
    }

    /** Cabeçalhos de uma navegação do Inertia (com a versão dos assets, senão vem 409). */
    private function inertia(): self
    {
        return $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request())]);
    }

    // ── Desligado (padrão): nada muda ─────────────────────────────────────

    public function test_desligado_login_normal_e_prop_demo_nula(): void
    {
        $this->get(route('login'))->assertInertia(fn ($page) => $page->component('Auth/Login')->where('demo', null));

        $this->post('/login', ['email' => $this->admin->email, 'password' => 'senha-forte-123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_desligado_rotas_de_demo_respondem_404(): void
    {
        $this->acessar()->assertNotFound();
        $this->actingAs($this->admin)->post(route('demo.perfil', 'consultor'))->assertNotFound();
        $this->get(route('demo.visitantes', ['token' => 'qualquer']))->assertNotFound();
        $this->assertSame(0, DemoVisitante::count());
    }

    public function test_desligado_criar_editar_e_excluir_funcionam(): void
    {
        $this->actingAs($this->admin)->get(route('admin.clientes.create'))->assertOk();
        $this->actingAs($this->admin)->post(route('admin.configuracoes.bancos.store'), ['nome' => 'Banco X', 'juros_mensal' => 1.5, 'qtd_parcelas' => 60, 'carencia' => 0, 'ativo' => true])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Banco::where('nome', 'Banco X')->exists());
    }

    // ── Ligado: acesso ───────────────────────────────────────────────────

    public function test_login_mostra_a_pagina_de_acesso_com_os_utms(): void
    {
        $this->ligar();

        $this->get(route('login', ['utm_source' => 'linkedin', 'utm_campaign' => 'out26', 'outro' => 'x']))
            ->assertInertia(fn ($page) => $page->component('Demo/Acesso')
                ->where('utm', ['utm_source' => 'linkedin', 'utm_campaign' => 'out26']));
    }

    public function test_acesso_registra_o_visitante_e_entra_no_perfil_inicial(): void
    {
        $this->ligar();

        $this->acessar()->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->admin);
        $visitante = DemoVisitante::sole();
        $this->assertSame('visitante@empresa.com', $visitante->email);
        $this->assertSame('11987654321', $visitante->telefone);
        $this->assertSame(['linkedin', 'lancamento', 1, ['admin'], 'admin'], [$visitante->utm_source, $visitante->utm_campaign, $visitante->visitas, $visitante->perfis_vistos, $visitante->ultimo_perfil]);
        $this->assertSame($visitante->id, session(ModoDemonstracao::SESSAO_VISITANTE));
    }

    public function test_validacao_do_acesso(): void
    {
        $this->ligar();

        $this->acessar(['email' => '', 'telefone' => '(11) 3333-4444'])->assertSessionHasNoErrors();
        $this->post('/logout');

        $this->acessar(['email' => '', 'telefone' => ''])->assertSessionHasErrors(['email', 'telefone']);
        $this->acessar(['aceite' => false])->assertSessionHasErrors('aceite');
        $this->acessar(['nome' => 'A', 'email' => 'invalido', 'telefone' => 'abc'])->assertSessionHasErrors(['nome', 'email', 'telefone']);
        $this->assertSame(1, DemoVisitante::count());
    }

    public function test_visitante_que_volta_nao_duplica_e_soma_visitas(): void
    {
        $this->ligar();

        $this->acessar();
        $this->post('/logout');
        $this->acessar(['email' => 'outro@email.com', 'nome' => 'Mesmo Telefone']); // mesmo telefone

        $visitante = DemoVisitante::sole();
        $this->assertSame(2, $visitante->visitas);
        $this->assertSame('Mesmo Telefone', $visitante->nome);
        $this->assertSame('linkedin', $visitante->utm_source); // origem da primeira visita
    }

    // ── Troca de perfil ──────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function perfis(): array
    {
        return array_combine(array_keys(ModoDemonstracao::PERFIS), array_map(fn ($p) => [$p], array_keys(ModoDemonstracao::PERFIS)));
    }

    #[DataProvider('perfis')]
    public function test_troca_de_perfil(string $perfil): void
    {
        $this->ligar();
        $this->acessar();

        $this->post(route('demo.perfil', $perfil))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($perfil === 'admin' ? $this->admin : $this->consultor);
        $this->assertContains($perfil, DemoVisitante::sole()->perfis_vistos);
        $this->assertSame($perfil, DemoVisitante::sole()->ultimo_perfil);
        $this->get(route('dashboard'))->assertRedirect(route($perfil.'.dashboard'));
    }

    public function test_troca_de_perfil_invalido_ou_sem_visitante(): void
    {
        $this->ligar();
        $this->acessar();
        $this->post(route('demo.perfil', 'superusuario'))->assertNotFound();

        // Logado sem passar pelo acesso (sem visitante na sessão): volta para o login.
        $this->post('/logout');
        $this->actingAs($this->admin)->post(route('demo.perfil', 'consultor'))->assertRedirect(route('login'));
    }

    public function test_usuario_do_perfil_cai_no_primeiro_ativo_e_sem_nenhum_da_erro_claro(): void
    {
        config(['demo.users.consultor' => 'nao-existe@crmsolar.demo']);
        $this->assertSame($this->consultor->id, app(ModoDemonstracao::class)->usuarioPara('consultor')->id);

        User::where('tipo', 'consultor')->update(['status' => false]);
        $this->expectExceptionMessage('Rode o seeder de demonstração');
        app(ModoDemonstracao::class)->usuarioPara('consultor');
    }

    public function test_props_da_demo_chegam_as_paginas(): void
    {
        $this->ligar();
        $this->acessar();

        $this->get(route('admin.dashboard'))->assertInertia(fn ($page) => $page
            ->where('demo.enabled', true)
            ->where('demo.role', 'admin')
            ->where('demo.visitor', 'Visitante Teste')
            ->where('demo.roles', [['key' => 'admin', 'label' => 'Administrador'], ['key' => 'consultor', 'label' => 'Consultor']])
            ->where('demo.allowed_paths', fn ($caminhos) => collect($caminhos)->contains('/consultor/orcamentos/grupo/b1/calcular')
                && collect($caminhos)->contains('/consultor/orcamentos/grupo/*/calcular')
                && collect($caminhos)->contains('/logout') && collect($caminhos)->contains('/demo/'))
            ->where('demo.message', ModoDemonstracao::AVISO_BLOQUEIO));
    }

    // ── Somente leitura no servidor ──────────────────────────────────────

    public function test_escrita_e_telas_de_formulario_sao_bloqueadas_no_servidor(): void
    {
        $this->ligar();
        $this->acessar();
        $banco = Banco::create(['nome' => 'Original', 'juros_mensal' => 1, 'qtd_parcelas' => 12, 'carencia' => 0]);
        $origem = route('admin.configuracoes.bancos.index');

        // Navegação (Inertia): volta com o aviso.
        foreach ([
            ['post', route('admin.configuracoes.bancos.store'), ['nome' => 'Novo', 'juros_mensal' => 1, 'qtd_parcelas' => 12, 'carencia' => 0]],
            ['put', route('admin.configuracoes.bancos.update', $banco), ['nome' => 'Alterado', 'juros_mensal' => 1, 'qtd_parcelas' => 12, 'carencia' => 0]],
            ['delete', route('admin.configuracoes.bancos.destroy', $banco), []],
            ['post', route('admin.configuracoes.identidade.update'), ['nome' => 'Hackeado']],
        ] as [$metodo, $url, $dados]) {
            $this->from($origem)->inertia()->{$metodo}($url, $dados)
                ->assertRedirect($origem)->assertSessionHas('warning', ModoDemonstracao::AVISO_BLOQUEIO);
            $this->flushHeaders();
        }
        $this->from($origem)->get(route('admin.clientes.create'))->assertRedirect($origem)->assertSessionHas('warning');
        $this->from($origem)->get(route('admin.perfil.senha'))->assertRedirect($origem)->assertSessionHas('warning');

        // API/axios: 403 em JSON.
        $this->postJson(route('admin.configuracoes.bancos.store'), ['nome' => 'Via API'])
            ->assertForbidden()->assertJson(['message' => ModoDemonstracao::AVISO_BLOQUEIO, 'demo' => true]);

        $this->assertSame(['Original'], Banco::pluck('nome')->all());
        $this->assertSame('CRM Solar', IdentidadeVisual::nome());
    }

    public function test_consultor_nao_salva_orcamento_mas_usa_o_simulador(): void
    {
        $this->ligar();
        $this->acessar();
        $this->post(route('demo.perfil', 'consultor'));

        // Tela do simulador (vitrine) e cálculo (POST que só lê) passam pelo bloqueio.
        $this->get(route('consultor.grupo.b1.create'))->assertOk();
        $this->postJson(route('consultor.grupo.b1.calcular'), [])->assertUnprocessable(); // chegou à validação
        // Salvar, não.
        $this->postJson(route('consultor.grupo.b1.store'), [])->assertForbidden();
        $orcamento = $this->orcamento($this->consultor);
        $this->post(route('consultor.funil.mover', $orcamento), [])->assertRedirect()->assertSessionHas('warning');
        $this->assertNull($orcamento->fresh()->funil_etapa_id);
    }

    public function test_paginas_de_leitura_abrem_e_contam_telas_vistas(): void
    {
        $this->ligar();
        $this->acessar();

        $this->get(route('admin.dashboard'))->assertOk();
        $this->inertia()->get(route('admin.clientes.index'))->assertOk();
        $this->flushHeaders();
        $this->getJson(route('api.estados'))->assertOk(); // chamada de dados: não é tela

        $this->assertSame(2, DemoVisitante::sole()->telas_vistas);
    }

    public function test_fluxos_de_senha_levam_ao_acesso(): void
    {
        $this->ligar();

        $this->get(route('password.request'))->assertRedirect(route('login'));
        $this->post(route('password.email'), ['email' => $this->admin->email])->assertRedirect(route('login'));
        $this->get(route('password.reset', 'token-qualquer'))->assertRedirect(route('login'));
        // Login com senha também não existe na demonstração.
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'senha-forte-123'])->assertRedirect();
        $this->assertGuest();
    }

    // ── Visitantes para a equipe comercial ───────────────────────────────

    public function test_csv_de_visitantes_so_com_o_token_correto(): void
    {
        $this->ligar();
        $this->acessar();
        $this->post(route('demo.perfil', 'consultor'));
        $this->post('/logout');

        $this->get(route('demo.visitantes'))->assertNotFound();
        $this->get(route('demo.visitantes', ['token' => 'errado']))->assertNotFound();

        $resposta = $this->get(route('demo.visitantes', ['token' => 'segredo-123']))->assertOk();
        $csv = $resposta->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFNome;E-mail;Telefone;", $csv);
        $this->assertStringContainsString('"Visitante Teste";visitante@empresa.com;11987654321;"Solar Ltda";', $csv);
        $this->assertStringContainsString('"Administrador, Consultor";linkedin;;lancamento', $csv);
        $this->assertStringContainsString('visitantes-demo-'.now()->format('Y-m-d').'.csv', $resposta->headers->get('Content-Disposition'));

        config(['demo.leads_token' => '']);
        $this->get(route('demo.visitantes', ['token' => '']))->assertNotFound();
    }

    public function test_comando_lista_e_exporta_visitantes(): void
    {
        $this->ligar();
        $this->acessar();
        $arquivo = tempnam(sys_get_temp_dir(), 'demo').'.csv';

        $this->artisan('demo:visitantes')->expectsOutputToContain('Visitante Teste')->assertSuccessful();
        $this->artisan('demo:visitantes', ['arquivo' => $arquivo, '--desde' => now()->subDay()->toDateString()])->assertSuccessful();
        $this->assertStringContainsString('visitante@empresa.com', file_get_contents($arquivo));
        $this->artisan('demo:visitantes', ['--desde' => '06/10/2026'])->assertFailed();

        @unlink($arquivo);
    }
}
