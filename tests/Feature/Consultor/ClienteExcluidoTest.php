<?php

namespace Tests\Feature\Consultor;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Cliente usa soft delete: orçamentos e contratos dele continuam exibindo os dados do cliente.
 */
class ClienteExcluidoTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    public function test_orcamento_de_cliente_excluido_continua_mostrando_o_cliente(): void
    {
        $consultor = $this->consultor();
        $cliente = $this->cliente($consultor, ['nome' => 'Cliente Removido', 'cpf' => '111.222.333-44']);
        $orcamento = $this->orcamento($consultor, ['cliente_id' => $cliente->id, 'status' => 'aprovado']);
        $this->itemKit($orcamento);
        $cliente->delete();

        $this->actingAs($consultor)->get(route('consultor.orcamentos.show', $orcamento))
            ->assertInertia(fn ($page) => $page->where('orcamento.cliente.nome', 'Cliente Removido'));

        $this->actingAs($consultor)->get(route('consultor.contratos.create', $orcamento))
            ->assertInertia(fn ($page) => $page
                ->where('sugestao.nome_cliente', 'Cliente Removido')
                ->where('sugestao.documento_cliente', '111.222.333-44'));

        $this->actingAs($this->admin())->get(route('admin.orcamentos.show', $orcamento))
            ->assertInertia(fn ($page) => $page->where('orcamento.cliente.nome', 'Cliente Removido'));
    }
}
