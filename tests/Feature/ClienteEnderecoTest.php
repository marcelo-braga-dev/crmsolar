<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Endereço do cliente (componente EnderecoFields): a cidade é obrigatória para o
 * dimensionamento, e a edição precisa receber a sigla da UF para pré-selecionar o estado.
 */
class ClienteEnderecoTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    public function test_admin_cria_cliente_com_cidade_e_ele_pode_ser_dimensionado(): void
    {
        $consultor = $this->consultor();
        $cidade = $this->cidade();

        $this->actingAs($this->admin())->post(route('admin.clientes.store'), [
            'consultor_id' => $consultor->id, 'tipo_pessoa' => 'pf', 'nome' => 'Com Cidade', 'status' => 'novo',
            'cep' => '60000-000', 'rua' => 'Rua A', 'bairro' => 'Centro', 'cidade_id' => $cidade->id,
        ])->assertSessionHasNoErrors();

        $cliente = Cliente::where('nome', 'Com Cidade')->sole();
        $this->assertSame($cidade->id, $cliente->cidade_id);

        // Sem cidade o cálculo recusa; com cidade ele segue (aqui falha só por falta dos demais campos).
        $this->actingAs($consultor)
            ->postJson(route('consultor.grupo.b1.calcular'), ['cliente_id' => $cliente->id])
            ->assertJsonMissingValidationErrors('cliente_id');
    }

    public function test_admin_rejeita_cidade_inexistente(): void
    {
        $this->actingAs($this->admin())->post(route('admin.clientes.store'), [
            'consultor_id' => $this->consultor()->id, 'tipo_pessoa' => 'pf', 'nome' => 'X', 'status' => 'novo', 'cidade_id' => 999999,
        ])->assertSessionHasErrors('cidade_id');
    }

    public function test_edicao_pelo_admin_envia_sigla_da_cidade(): void
    {
        $cliente = $this->cliente($this->consultor());

        $this->actingAs($this->admin())->get(route('admin.clientes.edit', $cliente))
            ->assertInertia(fn ($page) => $page->component('Admin/Clientes/Form')->where('cliente.cidade.sigla', 'CE'));
    }

    public function test_edicao_pelo_consultor_envia_sigla_da_cidade(): void
    {
        $consultor = $this->consultor();
        $cliente = $this->cliente($consultor);

        $this->actingAs($consultor)->get(route('consultor.clientes.edit', $cliente))
            ->assertInertia(fn ($page) => $page->component('Consultor/Clientes/Form')->where('cliente.cidade.sigla', 'CE'));
    }
}
