<?php

namespace Tests\Feature;

use App\Models\IntegracaoHistorico;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PDOException;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Exclusões barradas por vínculo devem voltar com aviso, nunca com erro 500.
 */
class ExclusaoComVinculosTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    public function test_fornecedor_com_historico_de_integracao_nao_e_excluido(): void
    {
        $fornecedor = $this->fornecedor();
        IntegracaoHistorico::create(['fornecedor_id' => $fornecedor->id, 'tipo' => 'edeltec', 'status' => 'concluido']);

        $this->actingAs($this->admin())
            ->delete(route('admin.fornecedores.destroy', $fornecedor))
            ->assertSessionHas('error');

        $this->assertNotNull($fornecedor->fresh());
    }

    public function test_violacao_de_chave_estrangeira_em_delete_vira_aviso(): void
    {
        Route::middleware('web')->delete('/_teste/fk', fn () => throw new QueryException(
            'sqlite', 'delete from x', [], new PDOException('FOREIGN KEY constraint failed', 23000),
        ));

        $this->from('/admin/dashboard')
            ->delete('/_teste/fk')
            ->assertRedirect('/admin/dashboard')
            ->assertSessionHas('error');
    }

    public function test_erro_de_integridade_fora_de_delete_continua_sendo_erro(): void
    {
        Route::middleware('web')->post('/_teste/fk', fn () => throw new QueryException(
            'sqlite', 'insert into x', [], new PDOException('UNIQUE constraint failed', 23000),
        ));

        $this->post('/_teste/fk')->assertServerError();
    }
}
