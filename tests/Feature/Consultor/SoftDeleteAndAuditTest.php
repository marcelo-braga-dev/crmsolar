<?php

namespace Tests\Feature\Consultor;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SoftDeleteAndAuditTest extends TestCase
{
    use RefreshDatabase;

    private function consultor(): User
    {
        return User::create([
            'name' => 'Consultor Auditoria', 'email' => 'auditoria@teste.com',
            'password' => 'senha', 'tipo' => 'consultor', 'status' => true,
        ]);
    }

    public function test_excluir_cliente_faz_soft_delete_em_vez_de_apagar_fisicamente(): void
    {
        $consultor = $this->consultor();
        $cliente = Cliente::create([
            'consultor_id' => $consultor->id, 'tipo_pessoa' => 'pf',
            'nome' => 'Cliente a Excluir', 'status' => 'novo',
        ]);

        $this->actingAs($consultor)
            ->delete(route('consultor.clientes.destroy', $cliente))
            ->assertRedirect();

        // A linha continua no banco (recuperável), só não aparece nas queries padrão.
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
        $this->assertNotNull(DB::table('clientes')->where('id', $cliente->id)->value('deleted_at'));
        $this->assertNull(Cliente::find($cliente->id));
        $this->assertNotNull(Cliente::withTrashed()->find($cliente->id));
    }

    public function test_criar_e_atualizar_cliente_gera_registro_de_auditoria(): void
    {
        $consultor = $this->consultor();

        $this->actingAs($consultor)->post(route('consultor.clientes.store'), [
            'tipo_pessoa' => 'pf', 'nome' => 'Cliente Auditado', 'status' => 'novo',
        ]);
        $cliente = Cliente::where('nome', 'Cliente Auditado')->firstOrFail();

        $this->actingAs($consultor)->put(route('consultor.clientes.update', $cliente), [
            'tipo_pessoa' => 'pf', 'nome' => 'Cliente Auditado (renomeado)', 'status' => 'novo',
        ]);

        $eventos = DB::table('activity_log')
            ->where('subject_type', Cliente::class)
            ->where('subject_id', $cliente->id)
            ->pluck('event')
            ->all();

        $this->assertContains('created', $eventos);
        $this->assertContains('updated', $eventos);

        $updated = DB::table('activity_log')
            ->where('subject_type', Cliente::class)
            ->where('subject_id', $cliente->id)
            ->where('event', 'updated')
            ->first();

        $this->assertSame($consultor->id, $updated->causer_id);
        $this->assertStringContainsString('Cliente Auditado (renomeado)', $updated->attribute_changes);
    }
}
