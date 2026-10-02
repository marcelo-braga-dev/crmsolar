<?php

namespace Tests\Feature\Consultor;

use App\Models\Cliente;
use App\Models\Lead;
use App\Models\PropostaServico;
use App\Models\User;
use App\Models\VisitaTecnica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function consultor(string $email): User
    {
        return User::create([
            'name' => 'Consultor '.$email, 'email' => $email,
            'password' => 'senha', 'tipo' => 'consultor', 'status' => true,
        ]);
    }

    private function cliente(User $dono): Cliente
    {
        return Cliente::create([
            'consultor_id' => $dono->id, 'tipo_pessoa' => 'pf',
            'nome' => 'Cliente de Teste', 'status' => 'novo',
        ]);
    }

    // ── Cliente ──────────────────────────────────────────────────────────

    public function test_consultor_nao_acessa_cliente_de_outro_consultor(): void
    {
        $dono = $this->consultor('dono.cliente@teste.com');
        $outro = $this->consultor('outro.cliente@teste.com');
        $cliente = $this->cliente($dono);

        $this->actingAs($outro)->get(route('consultor.clientes.show', $cliente))->assertForbidden();
        $this->actingAs($outro)->get(route('consultor.clientes.edit', $cliente))->assertForbidden();
        $this->actingAs($outro)->put(route('consultor.clientes.update', $cliente), ['tipo_pessoa' => 'pf', 'status' => 'novo'])->assertForbidden();
        $this->actingAs($outro)->delete(route('consultor.clientes.destroy', $cliente))->assertForbidden();
    }

    public function test_consultor_dono_acessa_e_edita_seu_cliente(): void
    {
        $dono = $this->consultor('dono2.cliente@teste.com');
        $cliente = $this->cliente($dono);

        $this->actingAs($dono)->get(route('consultor.clientes.show', $cliente))->assertOk();
        $this->actingAs($dono)->put(route('consultor.clientes.update', $cliente), [
            'tipo_pessoa' => 'pf', 'nome' => 'Nome Atualizado', 'status' => 'novo',
        ])->assertRedirect();

        $this->assertSame('Nome Atualizado', $cliente->fresh()->nome);
    }

    // ── Lead ─────────────────────────────────────────────────────────────

    public function test_consultor_nao_acessa_lead_de_outro_consultor(): void
    {
        $dono = $this->consultor('dono.lead@teste.com');
        $outro = $this->consultor('outro.lead@teste.com');
        $lead = Lead::create(['consultor_id' => $dono->id, 'nome' => 'Lead Teste', 'status' => 'novo']);

        $this->actingAs($outro)->get(route('consultor.leads.show', $lead))->assertForbidden();
        $this->actingAs($outro)->put(route('consultor.leads.update', $lead), ['status' => 'contatado'])->assertForbidden();
    }

    // ── Visita técnica (antes desta mudança, sem nenhuma checagem de dono) ─

    public function test_consultor_nao_acessa_visita_de_outro_consultor(): void
    {
        $dono = $this->consultor('dono.visita@teste.com');
        $outro = $this->consultor('outro.visita@teste.com');
        $cliente = $this->cliente($dono);
        $visita = VisitaTecnica::create([
            'consultor_id' => $dono->id, 'cliente_id' => $cliente->id,
            'data_agendada' => now()->addDay(), 'status' => 'agendada',
        ]);

        $this->actingAs($outro)->get(route('consultor.visitas.show', $visita))->assertForbidden();
        $this->actingAs($outro)->get(route('consultor.visitas.edit', $visita))->assertForbidden();
        $this->actingAs($outro)->put(route('consultor.visitas.update', $visita), [
            'data_agendada' => now()->addDays(2)->toDateTimeString(), 'status' => 'agendada',
        ])->assertForbidden();
        $this->actingAs($outro)->delete(route('consultor.visitas.destroy', $visita))->assertForbidden();
    }

    public function test_consultor_nao_agenda_visita_para_cliente_de_outro_consultor(): void
    {
        $dono = $this->consultor('dono2.visita@teste.com');
        $outro = $this->consultor('outro2.visita@teste.com');
        $clienteAlheio = $this->cliente($dono);

        $response = $this->actingAs($outro)->post(route('consultor.visitas.store'), [
            'cliente_id' => $clienteAlheio->id,
            'data_agendada' => now()->addDay()->toDateTimeString(),
        ]);

        $response->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('visitas_tecnicas', 0);
    }

    // ── Proposta de serviço ─────────────────────────────────────────────

    public function test_consultor_nao_acessa_proposta_de_outro_consultor(): void
    {
        $dono = $this->consultor('dono.proposta@teste.com');
        $outro = $this->consultor('outro.proposta@teste.com');
        $cliente = $this->cliente($dono);
        $proposta = PropostaServico::create([
            'consultor_id' => $dono->id, 'cliente_id' => $cliente->id,
            'titulo' => 'Proposta Teste', 'descricao' => 'resumo', 'conteudo' => 'conteúdo completo da proposta',
            'valor' => 1000, 'status' => 'rascunho',
        ]);

        $this->actingAs($outro)->get(route('consultor.proposta-servicos.show', $proposta))->assertForbidden();
        $this->actingAs($outro)->get(route('consultor.proposta-servicos.edit', $proposta))->assertForbidden();
        $this->actingAs($outro)->delete(route('consultor.proposta-servicos.destroy', $proposta))->assertForbidden();
    }

    public function test_consultor_nao_cria_proposta_para_cliente_de_outro_consultor(): void
    {
        $dono = $this->consultor('dono3.proposta@teste.com');
        $outro = $this->consultor('outro3.proposta@teste.com');
        $clienteAlheio = $this->cliente($dono);

        $response = $this->actingAs($outro)->post(route('consultor.proposta-servicos.store'), [
            'cliente_id' => $clienteAlheio->id,
            'titulo' => 'Proposta Indevida',
            'valor' => 500,
            'validade' => now()->addDays(10)->toDateString(),
            'conteudo' => 'Conteúdo qualquer com mais de dez caracteres.',
            'status' => 'rascunho',
        ]);

        $response->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('proposta_servicos', 0);
    }
}
