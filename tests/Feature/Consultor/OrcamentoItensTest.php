<?php

namespace Tests\Feature\Consultor;

use App\Models\CategoriaProduto;
use App\Models\CidadeEstado;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\OrcamentoInfo;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrcamentoItensTest extends TestCase
{
    use RefreshDatabase;

    private User $consultor;

    private Orcamento $orcamento;

    private Produto $produto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consultor = User::create([
            'name' => 'Consultor Itens', 'email' => 'consultor.itens@teste.com',
            'password' => 'senha', 'tipo' => 'consultor', 'status' => true,
        ]);
        $cidade = CidadeEstado::create(['cidade' => 'Fortaleza', 'estado' => 'Ceará', 'sigla' => 'CE']);
        $cliente = Cliente::create([
            'consultor_id' => $this->consultor->id, 'cidade_id' => $cidade->id,
            'tipo_pessoa' => 'pf', 'nome' => 'Cliente Itens', 'status' => 'novo',
        ]);
        $this->orcamento = Orcamento::create([
            'consultor_id' => $this->consultor->id, 'cliente_id' => $cliente->id, 'cidade_id' => $cidade->id,
            'status' => 'novo', 'grupo_tarifario' => 'B1', 'preco_total' => 0,
        ]);
        $categoria = CategoriaProduto::create(['nome' => 'Cabo', 'slug' => 'cabo', 'ativo' => true]);
        $this->produto = Produto::create([
            'categoria_id' => $categoria->id, 'nome' => 'Cabo solar 6mm',
            'preco_custo' => 100, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);
    }

    private function adicionar(array $dados)
    {
        return $this->actingAs($this->consultor)
            ->post(route('consultor.orcamentos.itens.store', $this->orcamento), $dados + ['quantidade' => 2]);
    }

    public function test_rejeita_produto_vendido_abaixo_do_custo(): void
    {
        $this->adicionar(['tipo' => 'produto', 'produto_id' => $this->produto->id, 'preco_venda_unitario' => 99.99])
            ->assertSessionHasErrors('preco_venda_unitario');

        $this->assertSame(0, $this->orcamento->itens()->count());
    }

    public function test_aceita_produto_pelo_custo_ou_acima_e_recalcula_total(): void
    {
        $this->adicionar(['tipo' => 'produto', 'produto_id' => $this->produto->id, 'preco_venda_unitario' => 130])
            ->assertSessionHasNoErrors();

        $this->assertEquals(260.00, (float) $this->orcamento->fresh()->preco_total);
    }

    public function test_rejeita_produto_inativo(): void
    {
        $this->produto->update(['ativo' => false]);

        $this->adicionar(['tipo' => 'produto', 'produto_id' => $this->produto->id, 'preco_venda_unitario' => 200])
            ->assertSessionHasErrors('produto_id');
    }

    public function test_item_sem_produto_vira_personalizado_e_exige_descricao(): void
    {
        $this->adicionar(['tipo' => 'produto', 'preco_venda_unitario' => 50])
            ->assertSessionHasErrors('descricao');

        $this->adicionar(['tipo' => 'produto', 'descricao' => 'Mão de obra extra', 'preco_venda_unitario' => 50])
            ->assertSessionHasNoErrors();

        $this->assertSame('personalizado', $this->orcamento->itens()->first()->tipo);
    }

    public function test_orcamento_bloqueado_nao_aceita_nem_remove_itens(): void
    {
        $this->adicionar(['tipo' => 'personalizado', 'descricao' => 'Item', 'preco_venda_unitario' => 10])->assertSessionHasNoErrors();
        $item = $this->orcamento->itens()->first();
        OrcamentoInfo::create(['orcamento_id' => $this->orcamento->id, 'bloquear_edicao' => true]);

        $this->adicionar(['tipo' => 'personalizado', 'descricao' => 'Outro', 'preco_venda_unitario' => 10])
            ->assertForbidden();
        $this->actingAs($this->consultor)
            ->delete(route('consultor.orcamentos.itens.destroy', [$this->orcamento, $item]))
            ->assertForbidden();
    }
}
