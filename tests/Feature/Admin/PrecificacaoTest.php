<?php

namespace Tests\Feature\Admin;

use App\Models\MargemEstado;
use App\Models\MargemFornecedor;
use App\Models\MargemPrincipal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

class PrecificacaoTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    public function test_index_lista_os_27_estados_e_fornecedores_com_margem(): void
    {
        $fornecedor = $this->fornecedor(['nome' => 'Aldo']);
        MargemFornecedor::create(['fornecedor_id' => $fornecedor->id, 'margem' => 3]);
        MargemEstado::create(['estado' => 'CE', 'nome_estado' => 'Ceará', 'margem' => 2.5]);

        $this->actingAs($this->admin())->get(route('admin.precificacao.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Precificacao/Index')
                ->has('estados', 27)
                ->where('fornecedores.0.margem', 3)
                ->etc());
    }

    public function test_crud_de_faixa_de_margem_principal(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.precificacao.faixas.store'), [
            'nome' => 'Até 10 kWp', 'potencia_min' => 0, 'potencia_max' => 10, 'margem' => 25, 'ordem' => 1,
        ])->assertSessionHasNoErrors();
        $faixa = MargemPrincipal::firstOrFail();

        $this->actingAs($admin)->put(route('admin.precificacao.faixas.update', $faixa), [
            'nome' => 'Até 10 kWp', 'potencia_min' => 0, 'potencia_max' => 10, 'margem' => 22, 'ordem' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(22, (float) $faixa->fresh()->margem);

        $this->actingAs($admin)->delete(route('admin.precificacao.faixas.destroy', $faixa));
        $this->assertDatabaseCount('margens_principal', 0);
    }

    public function test_faixa_exige_potencia_max_maior_que_min_e_margem_ate_100(): void
    {
        $this->actingAs($this->admin())->post(route('admin.precificacao.faixas.store'), [
            'nome' => 'Inválida', 'potencia_min' => 10, 'potencia_max' => 5, 'margem' => 120, 'ordem' => 1,
        ])->assertSessionHasErrors(['potencia_max', 'margem']);
    }

    public function test_margem_por_estado_cria_e_depois_atualiza(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.precificacao.estados.update'), ['estado' => 'SP', 'margem' => 4]);
        $this->actingAs($admin)->post(route('admin.precificacao.estados.update'), ['estado' => 'SP', 'margem' => 6]);

        $this->assertDatabaseCount('margens_estados', 1);
        $this->assertDatabaseHas('margens_estados', ['estado' => 'SP', 'nome_estado' => 'São Paulo', 'margem' => 6]);
    }

    public function test_margem_por_estado_rejeita_uf_invalida(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.precificacao.estados.update'), ['estado' => 'XX', 'margem' => 4])
            ->assertSessionHasErrors('estado');
    }

    public function test_margem_por_fornecedor_cria_e_depois_atualiza(): void
    {
        $admin = $this->admin();
        $fornecedor = $this->fornecedor();

        $this->actingAs($admin)->post(route('admin.precificacao.fornecedores.update'), ['fornecedor_id' => $fornecedor->id, 'margem' => 1]);
        $this->actingAs($admin)->post(route('admin.precificacao.fornecedores.update'), ['fornecedor_id' => $fornecedor->id, 'margem' => 2]);

        $this->assertSame(1, MargemFornecedor::count());
        $this->assertEquals(2, (float) MargemFornecedor::value('margem'));
    }
}
