<?php

namespace Tests\Feature\Consultor;

use App\Models\Cliente;
use App\Models\PropostaServico;
use App\Models\VisitaTecnica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Clientes, Leads, Visitas e Propostas de serviço — fluxo feliz e escopo por consultor.
 * (Bloqueios de acesso a registros alheios estão em AuthorizationTest.)
 */
class CadastrosConsultorTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    // ── Clientes ─────────────────────────────────────────────────────────

    public function test_cliente_criado_pertence_ao_consultor_logado(): void
    {
        $eu = $this->consultor();
        $outro = $this->consultor();

        $this->actingAs($eu)->post(route('consultor.clientes.store'), [
            'tipo_pessoa' => 'pf', 'nome' => 'Cliente Novo', 'status' => 'novo',
            'consultor_id' => $outro->id, // ignorado
        ])->assertRedirect(route('consultor.clientes.index'));

        $this->assertSame($eu->id, Cliente::where('nome', 'Cliente Novo')->value('consultor_id'));
    }

    public function test_cliente_valida_tipo_pessoa_status_e_email(): void
    {
        $this->actingAs($this->consultor())
            ->post(route('consultor.clientes.store'), ['tipo_pessoa' => 'x', 'status' => 'y', 'email' => 'invalido'])
            ->assertSessionHasErrors(['tipo_pessoa', 'status', 'email']);
    }

    public function test_listagem_de_clientes_e_restrita_e_filtravel(): void
    {
        $eu = $this->consultor();
        $this->cliente($eu, ['nome' => 'Paula Mendes', 'status' => 'novo']);
        $this->cliente($eu, ['nome' => 'Rui', 'status' => 'finalizado']);
        $this->cliente($this->consultor(), ['nome' => 'Paula de outro']);

        $this->actingAs($eu)->get(route('consultor.clientes.index'))
            ->assertInertia(fn ($page) => $page->has('clientes.data', 2));
        $this->actingAs($eu)->get(route('consultor.clientes.index', ['search' => 'Paula']))
            ->assertInertia(fn ($page) => $page->has('clientes.data', 1));
        $this->actingAs($eu)->get(route('consultor.clientes.index', ['status' => 'finalizado']))
            ->assertInertia(fn ($page) => $page->has('clientes.data', 1)->where('clientes.data.0.nome', 'Rui'));
    }

    // ── Leads ────────────────────────────────────────────────────────────

    public function test_consultor_ve_apenas_seus_leads_e_atualiza_status(): void
    {
        $eu = $this->consultor();
        $meu = $this->lead($eu);
        $this->lead($this->consultor());
        $this->lead(); // sem consultor

        $this->actingAs($eu)->get(route('consultor.leads.index'))
            ->assertInertia(fn ($page) => $page->has('leads.data', 1));
        $this->actingAs($eu)->get(route('consultor.leads.show', $meu))->assertOk();

        $this->actingAs($eu)->put(route('consultor.leads.update', $meu), ['status' => 'convertido', 'anotacoes' => 'Fechou'])
            ->assertSessionHasNoErrors();
        $this->assertSame('convertido', $meu->fresh()->status);
    }

    public function test_lead_rejeita_status_invalido(): void
    {
        $eu = $this->consultor();

        $this->actingAs($eu)->put(route('consultor.leads.update', $this->lead($eu)), ['status' => 'em_negociacao'])
            ->assertSessionHasErrors('status');
    }

    // ── Visitas ──────────────────────────────────────────────────────────

    public function test_agenda_mostra_e_atualiza_visita(): void
    {
        $eu = $this->consultor();
        $cliente = $this->cliente($eu);
        $orcamento = $this->orcamento($eu, ['cliente_id' => $cliente->id, 'status' => 'aprovado']);

        $this->actingAs($eu)->post(route('consultor.visitas.store'), [
            'cliente_id' => $cliente->id, 'orcamento_id' => $orcamento->id, 'data_agendada' => now()->addDay()->toDateTimeString(),
        ])->assertRedirect(route('consultor.visitas.index'));

        $visita = VisitaTecnica::firstOrFail();
        $this->assertSame('agendada', $visita->status);
        $this->assertSame($eu->id, $visita->consultor_id);

        $this->actingAs($eu)->get(route('consultor.visitas.show', $visita))
            ->assertInertia(fn ($page) => $page->component('Consultor/Visitas/Show')->where('visita.id', $visita->id));

        $this->actingAs($eu)->put(route('consultor.visitas.update', $visita), [
            'data_agendada' => now()->addDays(2)->toDateTimeString(), 'status' => 'realizada', 'anotacoes' => 'OK',
        ])->assertSessionHasNoErrors();
        $this->assertSame('realizada', $visita->fresh()->status);

        $this->actingAs($eu)->delete(route('consultor.visitas.destroy', $visita))->assertRedirect(route('consultor.visitas.index'));
        $this->assertSoftDeleted($visita);
    }

    public function test_formulario_de_visita_lista_orcamentos_aprovados_ou_instalando(): void
    {
        $eu = $this->consultor();
        $this->orcamento($eu, ['status' => 'aprovado']);
        $this->orcamento($eu, ['status' => 'instalando']);
        $this->orcamento($eu, ['status' => 'novo']);

        $this->actingAs($eu)->get(route('consultor.visitas.create'))
            ->assertInertia(fn ($page) => $page->has('orcamentos', 2)->has('clientes', 3));
    }

    public function test_visita_nao_vincula_orcamento_de_outro_consultor(): void
    {
        $eu = $this->consultor();
        $alheio = $this->orcamento($this->consultor());

        $this->actingAs($eu)->post(route('consultor.visitas.store'), [
            'cliente_id' => $this->cliente($eu)->id, 'orcamento_id' => $alheio->id, 'data_agendada' => now()->toDateTimeString(),
        ])->assertSessionHasErrors('orcamento_id');
    }

    // ── Propostas de serviço ─────────────────────────────────────────────

    private function dadosProposta(int $clienteId, array $extra = []): array
    {
        return $extra + [
            'cliente_id' => $clienteId, 'titulo' => 'Limpeza de placas', 'valor' => 450,
            'validade' => now()->addWeek()->toDateString(), 'status' => 'rascunho',
            'conteudo' => '<p>Limpeza completa de 20 módulos com água desmineralizada.</p>',
        ];
    }

    public function test_crud_de_proposta_de_servico(): void
    {
        $eu = $this->consultor();
        $cliente = $this->cliente($eu);

        $this->actingAs($eu)->post(route('consultor.proposta-servicos.store'), $this->dadosProposta($cliente->id))
            ->assertSessionHasNoErrors();
        $proposta = PropostaServico::firstOrFail();
        $this->assertSame($eu->id, $proposta->consultor_id);
        $this->assertStringNotContainsString('<p>', $proposta->descricao);

        $this->actingAs($eu)->get(route('consultor.proposta-servicos.show', $proposta))->assertOk();
        $this->actingAs($eu)->put(route('consultor.proposta-servicos.update', $proposta), $this->dadosProposta($cliente->id, ['status' => 'aceita']))
            ->assertSessionHasNoErrors();
        $this->assertSame('aceita', $proposta->fresh()->status);

        $this->actingAs($eu)->get(route('consultor.proposta-servicos.index'))
            ->assertInertia(fn ($page) => $page->where('stats.aceitas', 1)->where('stats.valor_aceito', 450));

        $this->actingAs($eu)->delete(route('consultor.proposta-servicos.destroy', $proposta));
        $this->assertSoftDeleted($proposta);
    }

    public function test_proposta_nova_nao_aceita_validade_passada_nem_status_final(): void
    {
        $eu = $this->consultor();

        $this->actingAs($eu)->post(route('consultor.proposta-servicos.store'), $this->dadosProposta($this->cliente($eu)->id, [
            'validade' => now()->subDay()->toDateString(), 'status' => 'aceita',
        ]))->assertSessionHasErrors(['validade', 'status']);
    }
}
