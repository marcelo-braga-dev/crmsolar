<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Matriz de acesso das telas (GET sem parâmetro): visitante → login,
 * papel errado → 403, papel certo → 200.
 */
class ControleDeAcessoTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function telasAdmin(): array
    {
        return array_combine($r = [
            'admin.dashboard', 'admin.funil.index', 'admin.configuracoes.funil.index', 'admin.orcamentos.index', 'admin.clientes.index', 'admin.clientes.create',
            'admin.leads.index', 'admin.produtos.kits.index', 'admin.produtos.kits.create',
            'admin.produtos.catalogo.index', 'admin.produtos.catalogo.create',
            'admin.usuarios.consultores.index', 'admin.usuarios.consultores.create',
            'admin.usuarios.admins.index', 'admin.usuarios.admins.create', 'admin.financeiro.comissoes.index',
            'admin.financeiro.faturamento', 'admin.precificacao.index', 'admin.fornecedores.index',
            'admin.fornecedores.create', 'admin.integracoes.edeltec',
            'admin.integracoes.historico', 'admin.configuracoes.bancos.index',
            'admin.configuracoes.concessionarias.index', 'admin.configuracoes.dimensionamento',
            'admin.configuracoes.sistema', 'admin.configuracoes.auditoria', 'admin.configuracoes.identidade', 'admin.perfil.edit', 'admin.perfil.senha',
        ], array_map(fn ($n) => [$n], $r));
    }

    /** @return array<string, array{string}> */
    public static function telasConsultor(): array
    {
        return array_combine($r = [
            'consultor.dashboard', 'consultor.funil.index', 'consultor.orcamentos.index', 'consultor.orcamentos.selecionar_grupo',
            'consultor.grupo.b1.create', 'consultor.grupo.b2.create', 'consultor.grupo.b3.create',
            'consultor.dimensionamento.convencional', 'consultor.dimensionamento.demanda',
            'consultor.clientes.index', 'consultor.clientes.create', 'consultor.proposta-servicos.index',
            'consultor.proposta-servicos.create', 'consultor.leads.index', 'consultor.visitas.index',
            'consultor.visitas.create', 'consultor.contratos.index', 'consultor.financeiro',
            'consultor.perfil.edit', 'consultor.perfil.senha',
        ], array_map(fn ($n) => [$n], $r));
    }

    #[DataProvider('telasAdmin')]
    public function test_telas_admin(string $rota): void
    {
        $this->get(route($rota))->assertRedirect(route('login'));
        $this->actingAs($this->consultor())->get(route($rota))->assertForbidden();
        $this->actingAs($this->admin())->get(route($rota))->assertOk();
    }

    #[DataProvider('telasConsultor')]
    public function test_telas_consultor(string $rota): void
    {
        $this->get(route($rota))->assertRedirect(route('login'));
        $this->actingAs($this->admin())->get(route($rota))->assertForbidden();
        $this->actingAs($this->consultor())->get(route($rota))->assertOk();
    }

    public function test_grupo_a_aceita_apenas_subgrupos_validos(): void
    {
        $consultor = $this->consultor();

        foreach (['A4', 'A3a', 'A3', 'A2', 'A1'] as $grupo) {
            $this->actingAs($consultor)->get(route('consultor.grupo.a.create', $grupo))->assertOk();
        }
        $this->actingAs($consultor)->get('/consultor/orcamentos/grupo/A9/create')->assertNotFound();
    }

    public function test_dashboard_redireciona_conforme_papel(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->actingAs($this->admin())->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->consultor())->get(route('dashboard'))->assertRedirect(route('consultor.dashboard'));
    }

    public function test_cadastro_publico_esta_desativado(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Intruso', 'email' => 'intruso@teste.com',
            'password' => 'senha-forte-123', 'password_confirmation' => 'senha-forte-123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@teste.com']);
    }

    public function test_verificacao_de_email_do_breeze_nao_existe(): void
    {
        // Contas são criadas pelo Admin; User não implementa MustVerifyEmail.
        foreach (['verification.notice', 'verification.verify', 'verification.send'] as $rota) {
            $this->assertFalse(Route::has($rota), "Rota {$rota} não deveria existir");
        }

        $this->actingAs($this->consultor())->get('/verify-email')->assertNotFound();
    }
}
