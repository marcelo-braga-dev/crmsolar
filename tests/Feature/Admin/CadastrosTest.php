<?php

namespace Tests\Feature\Admin;

use App\Models\Banco;
use App\Models\Concessionaria;
use App\Models\Config;
use App\Models\Fornecedor;
use App\Models\ParamDimensionamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Fornecedores e Configurações (bancos, concessionárias, dimensionamento, sistema).
 */
class CadastrosTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    // ── Fornecedores ─────────────────────────────────────────────────────

    public function test_crud_de_fornecedor(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.fornecedores.store'), ['nome' => 'Edeltec', 'ativo' => true])
            ->assertRedirect(route('admin.fornecedores.index'));
        $fornecedor = Fornecedor::where('nome', 'Edeltec')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.fornecedores.show', $fornecedor))
            ->assertInertia(fn ($page) => $page->where('fornecedor.nome', 'Edeltec'));
        $this->actingAs($admin)->get(route('admin.fornecedores.edit', $fornecedor))
            ->assertInertia(fn ($page) => $page->where('fornecedor.id', $fornecedor->id));

        $this->actingAs($admin)->put(route('admin.fornecedores.update', $fornecedor), ['nome' => 'Edeltec Solar', 'ativo' => false]);
        $this->assertSame('Edeltec Solar', $fornecedor->fresh()->nome);

        $this->actingAs($admin)->delete(route('admin.fornecedores.destroy', $fornecedor));
        $this->assertNull(Fornecedor::find($fornecedor->id));
    }

    public function test_fornecedor_com_kits_nao_pode_ser_excluido(): void
    {
        $fornecedor = $this->fornecedor();
        $this->kit(['fornecedor_id' => $fornecedor->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.fornecedores.destroy', $fornecedor))
            ->assertSessionHas('error');

        $this->assertNotNull($fornecedor->fresh());
    }

    public function test_fornecedor_valida_email_e_site(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.fornecedores.store'), ['nome' => 'X', 'email' => 'invalido', 'site' => 'nao-e-url', 'ativo' => true])
            ->assertSessionHasErrors(['email', 'site']);
    }

    // ── Bancos ───────────────────────────────────────────────────────────

    public function test_crud_de_banco(): void
    {
        $admin = $this->admin();
        $dados = ['nome' => 'Banco Solar', 'juros_mensal' => 1.49, 'qtd_parcelas' => 60, 'carencia' => 3, 'ativo' => true];

        $this->actingAs($admin)->post(route('admin.configuracoes.bancos.store'), $dados)->assertSessionHasNoErrors();
        $banco = Banco::firstOrFail();

        $this->actingAs($admin)->put(route('admin.configuracoes.bancos.update', $banco), ['qtd_parcelas' => 72] + $dados);
        $this->assertSame(72, (int) $banco->fresh()->qtd_parcelas);

        $this->actingAs($admin)->delete(route('admin.configuracoes.bancos.destroy', $banco));
        $this->assertDatabaseCount('bancos', 0);
    }

    public function test_banco_valida_limites(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.configuracoes.bancos.store'), ['nome' => 'X', 'juros_mensal' => 101, 'qtd_parcelas' => 0, 'carencia' => 13, 'ativo' => true])
            ->assertSessionHasErrors(['juros_mensal', 'qtd_parcelas', 'carencia']);
    }

    // ── Concessionárias ──────────────────────────────────────────────────

    public function test_crud_de_concessionaria(): void
    {
        $admin = $this->admin();
        $dados = ['nome' => 'Enel CE', 'estado' => 'CE', 'tarifa_convencional' => 0.85,
            'tarifa_ponta' => 2.1, 'tarifa_fora_ponta' => 0.6, 'ativo' => true];

        $this->actingAs($admin)->post(route('admin.configuracoes.concessionarias.store'), $dados)->assertSessionHasNoErrors();
        $concessionaria = Concessionaria::firstOrFail();

        $this->actingAs($admin)->put(route('admin.configuracoes.concessionarias.update', $concessionaria), ['tarifa_convencional' => 0.9] + $dados);
        $this->assertEquals(0.9, (float) $concessionaria->fresh()->tarifa_convencional);

        $this->actingAs($admin)->get(route('admin.configuracoes.concessionarias.index', ['estado' => 'CE']))
            ->assertInertia(fn ($page) => $page->has('concessionarias.data', 1)->where('estados', ['CE']));

        $this->actingAs($admin)->delete(route('admin.configuracoes.concessionarias.destroy', $concessionaria));
        $this->assertDatabaseCount('concessionarias', 0);
    }

    public function test_concessionaria_exige_tarifas_de_ponta_e_fora_ponta(): void
    {
        // Colunas NOT NULL no banco — antes a validação aceitava vazio e dava erro 500.
        $this->actingAs($this->admin())
            ->post(route('admin.configuracoes.concessionarias.store'), ['nome' => 'X', 'estado' => 'CE', 'tarifa_convencional' => 1, 'ativo' => true])
            ->assertSessionHasErrors(['tarifa_ponta', 'tarifa_fora_ponta']);
    }

    // ── Dimensionamento e Sistema ────────────────────────────────────────

    public function test_atualiza_parametros_de_dimensionamento(): void
    {
        $param = ParamDimensionamento::create(['chave' => 'fator_perda_sistema', 'nome' => 'Perda do sistema', 'valor' => '20']);

        $this->actingAs($this->admin())
            ->put(route('admin.configuracoes.dimensionamento.update'), ['params' => [['id' => $param->id, 'valor' => '18']]])
            ->assertSessionHasNoErrors();

        $this->assertSame('18', $param->fresh()->valor);
    }

    public function test_parametro_de_dimensionamento_inexistente_e_rejeitado(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.configuracoes.dimensionamento.update'), ['params' => [['id' => 999, 'valor' => '1']]])
            ->assertSessionHasErrors('params.0.id');
    }

    public function test_salva_configuracoes_do_sistema(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.configuracoes.sistema.update'), ['configs' => [
                ['chave' => 'empresa_nome', 'valor' => 'Rexar Solar', 'grupo' => 'empresa'],
                ['chave' => 'proposta_validade_dias', 'valor' => '15', 'grupo' => 'proposta'],
            ]])
            ->assertSessionHasNoErrors();

        $this->assertSame('Rexar Solar', Config::get('empresa_nome'));
        $this->assertSame('15', Config::get('proposta_validade_dias'));
    }

    // ── Integrações ──────────────────────────────────────────────────────

    public function test_edeltec_sem_credenciais_nao_tenta_integrar(): void
    {
        config(['services.edeltec.api_key' => null]);

        $this->actingAs($this->admin())
            ->post(route('admin.integracoes.distribuidora.integrar'))
            ->assertSessionHas('error');
    }
}
