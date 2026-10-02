<?php

namespace Tests\Feature\Admin;

use App\Models\Cliente;
use App\Models\OrcamentoHistorico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Orçamentos, Leads, Clientes, Financeiro e Dashboard na visão do Admin.
 */
class OperacionalTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    // ── Orçamentos ───────────────────────────────────────────────────────

    public function test_admin_ve_orcamentos_de_todos_e_filtra_por_consultor(): void
    {
        $ana = $this->consultor();
        $bruno = $this->consultor();
        $this->orcamento($ana);
        $this->orcamento($bruno);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.orcamentos.index'))
            ->assertInertia(fn ($page) => $page->has('orcamentos.data', 2)->where('stats.total', 2));
        $this->actingAs($admin)->get(route('admin.orcamentos.index', ['consultor_id' => $ana->id]))
            ->assertInertia(fn ($page) => $page->has('orcamentos.data', 1));
    }

    public function test_admin_abre_orcamento_de_qualquer_consultor(): void
    {
        $orcamento = $this->orcamento($this->consultor());

        $this->actingAs($this->admin())->get(route('admin.orcamentos.show', $orcamento))
            ->assertInertia(fn ($page) => $page->component('Admin/Orcamentos/Show')->where('orcamento.id', $orcamento->id));
    }

    public function test_mudanca_de_status_pelo_admin_gera_historico(): void
    {
        $orcamento = $this->orcamento($this->consultor(), ['status' => 'aprovando']);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.orcamentos.update', $orcamento), ['status' => 'aprovado'])
            ->assertSessionHasNoErrors();

        $this->assertSame('aprovado', $orcamento->fresh()->status);
        $this->assertDatabaseHas('orcamento_historicos', [
            'orcamento_id' => $orcamento->id, 'usuario_id' => $admin->id, 'status' => 'aprovado',
        ]);
    }

    public function test_salvar_sem_mudar_status_nao_gera_historico(): void
    {
        $orcamento = $this->orcamento($this->consultor(), ['status' => 'aprovando']);

        $this->actingAs($this->admin())->put(route('admin.orcamentos.update', $orcamento), ['status' => 'aprovando', 'anotacoes' => 'ok']);

        $this->assertSame(0, OrcamentoHistorico::count());
    }

    public function test_status_invalido_e_rejeitado(): void
    {
        $orcamento = $this->orcamento($this->consultor());

        $this->actingAs($this->admin())->put(route('admin.orcamentos.update', $orcamento), ['status' => 'assinado'])
            ->assertSessionHasErrors('status');
    }

    // ── Leads ────────────────────────────────────────────────────────────

    public function test_admin_encaminha_lead_para_consultor(): void
    {
        $lead = $this->lead();
        $consultor = $this->consultor();

        $this->actingAs($this->admin())
            ->put(route('admin.leads.update', $lead), ['status' => 'encaminhado', 'consultor_id' => $consultor->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($consultor->id, $lead->fresh()->consultor_id);
    }

    public function test_lead_nao_pode_ser_encaminhado_para_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.leads.update', $this->lead()), ['status' => 'encaminhado', 'consultor_id' => $admin->id])
            ->assertSessionHasErrors('consultor_id');
    }

    public function test_admin_busca_leads(): void
    {
        $this->lead(null, ['nome' => 'Maria Solar']);
        $this->lead(null, ['nome' => 'João']);

        $this->actingAs($this->admin())->get(route('admin.leads.index', ['search' => 'Maria']))
            ->assertInertia(fn ($page) => $page->has('leads.data', 1));
    }

    // ── Clientes ─────────────────────────────────────────────────────────

    public function test_admin_cria_cliente_para_um_consultor(): void
    {
        $consultor = $this->consultor();

        $this->actingAs($this->admin())->post(route('admin.clientes.store'), [
            'consultor_id' => $consultor->id, 'tipo_pessoa' => 'pj', 'razao_social' => 'Empresa Solar Ltda', 'status' => 'novo',
        ])->assertRedirect(route('admin.clientes.index'));

        $this->assertDatabaseHas('clientes', ['razao_social' => 'Empresa Solar Ltda', 'consultor_id' => $consultor->id]);
    }

    public function test_cliente_nao_pode_pertencer_a_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.clientes.store'), [
            'consultor_id' => $admin->id, 'tipo_pessoa' => 'pf', 'nome' => 'X', 'status' => 'novo',
        ])->assertSessionHasErrors('consultor_id');
    }

    public function test_admin_edita_e_exclui_cliente_com_soft_delete(): void
    {
        $consultor = $this->consultor();
        $cliente = $this->cliente($consultor);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.clientes.show', $cliente))->assertOk();
        $this->actingAs($admin)->put(route('admin.clientes.update', $cliente), [
            'consultor_id' => $consultor->id, 'tipo_pessoa' => 'pf', 'nome' => 'Nome Editado', 'status' => 'finalizado',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Nome Editado', $cliente->fresh()->nome);

        $this->actingAs($admin)->delete(route('admin.clientes.destroy', $cliente));
        $this->assertSoftDeleted($cliente);
        $this->assertNotNull(Cliente::withTrashed()->find($cliente->id));
    }

    // ── Financeiro ───────────────────────────────────────────────────────

    public function test_faturamento_considera_aprovado_instalando_e_finalizado(): void
    {
        $consultor = $this->consultor();
        foreach (['novo' => 1000, 'aprovando' => 2000, 'aprovado' => 10000, 'instalando' => 20000, 'finalizado' => 30000] as $status => $valor) {
            $this->orcamento($consultor, ['status' => $status, 'preco_total' => $valor]);
        }

        $this->actingAs($this->admin())->get(route('admin.financeiro.faturamento'))
            ->assertInertia(fn ($page) => $page->has('orcamentos.data', 3)->where('total', fn ($t) => (float) $t === 60000.0));
    }

    public function test_comissoes_listam_itens_de_orcamentos_fechados_e_consultores(): void
    {
        $consultor = $this->consultor(['name' => 'Carla']);
        $this->itemKit($this->orcamento($consultor, ['status' => 'aprovado']));
        $this->itemKit($this->orcamento($consultor, ['status' => 'novo']));

        $this->actingAs($this->admin())->get(route('admin.financeiro.comissoes.index'))
            ->assertInertia(fn ($page) => $page
                ->has('comissoes.data', 1)
                ->has('vendedores', 1)
                ->where('vendedores.0.name', 'Carla'));
    }

    // ── Dashboard ────────────────────────────────────────────────────────

    public function test_dashboard_calcula_kpis(): void
    {
        $consultor = $this->consultor();
        $this->orcamento($consultor, ['status' => 'aprovado', 'preco_total' => 15000]);
        $this->orcamento($consultor, ['status' => 'instalando', 'preco_total' => 5000]);
        $this->orcamento($consultor, ['status' => 'novo', 'preco_total' => 99999]);
        $this->lead($consultor, ['status' => 'encaminhado']);
        $this->lead($consultor, ['status' => 'perdido']);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.orcamentos_mes', 3)
                ->where('stats.valor_aprovado_total', 20000)
                ->where('stats.em_instalacao', 1)
                ->where('stats.leads_abertos', 1)
                ->where('stats.consultores_ativos', 1)
                ->has('evolucao', 6)
                ->has('recentes', 3)
                ->etc());
    }
}
