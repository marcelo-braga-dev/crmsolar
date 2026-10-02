<?php

namespace Tests\Feature\Admin;

use App\Models\IntegracaoHistorico;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Integração Aldo descontinuada: nenhuma rota, tabela ou tipo de histórico deve restar.
 */
class IntegracaoAldoRemovidaTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    public function test_rotas_da_aldo_nao_existem(): void
    {
        $this->assertFalse(Route::has('admin.integracoes.aldo'));
        $this->assertFalse(Route::has('admin.integracoes.aldo.integrar'));

        $this->actingAs($this->admin())->get('/admin/integracoes/aldo')->assertNotFound();
    }

    public function test_tabela_de_mapeamentos_da_aldo_foi_removida(): void
    {
        $this->assertFalse(Schema::hasTable('integracao_aldo_mapeamentos'));
        $this->assertTrue(Schema::hasTable('integracao_edeltec_mapeamentos'));
    }

    public function test_historico_nao_aceita_mais_o_tipo_aldo(): void
    {
        $fornecedor = $this->fornecedor();

        IntegracaoHistorico::create(['fornecedor_id' => $fornecedor->id, 'tipo' => 'edeltec', 'status' => 'concluido']);

        $this->expectException(QueryException::class);
        IntegracaoHistorico::create(['fornecedor_id' => $fornecedor->id, 'tipo' => 'aldo', 'status' => 'concluido']);
    }

    public function test_tela_de_historico_continua_funcionando_com_a_edeltec(): void
    {
        IntegracaoHistorico::create(['fornecedor_id' => $this->fornecedor()->id, 'tipo' => 'edeltec', 'status' => 'concluido']);

        $this->actingAs($this->admin())->get(route('admin.integracoes.historico', ['tipo' => 'edeltec']))
            ->assertInertia(fn ($page) => $page->has('historicos.data', 1));
    }
}
