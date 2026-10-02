<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    private function dadosConsultor(array $extra = []): array
    {
        return $extra + [
            'name' => 'Novo Consultor', 'email' => 'novo.consultor@teste.com',
            'password' => 'senha-forte-123', 'password_confirmation' => 'senha-forte-123',
            'comissao_percentual' => 7.5, 'status' => true,
        ];
    }

    // ── Consultores ──────────────────────────────────────────────────────

    public function test_admin_cria_consultor_com_senha_hasheada(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.usuarios.consultores.store'), $this->dadosConsultor())
            ->assertRedirect(route('admin.usuarios.consultores.index'));

        $user = User::where('email', 'novo.consultor@teste.com')->firstOrFail();
        $this->assertSame('consultor', $user->tipo);
        $this->assertEquals(7.5, (float) $user->comissao_percentual);
        $this->assertTrue(Hash::check('senha-forte-123', $user->password));
    }

    public function test_tipo_enviado_e_ignorado_consultor_sempre_e_consultor(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.usuarios.consultores.store'), $this->dadosConsultor(['tipo' => 'admin']));

        $this->assertSame('consultor', User::where('email', 'novo.consultor@teste.com')->value('tipo'));
    }

    public function test_comissao_vazia_vira_zero(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.usuarios.consultores.store'), $this->dadosConsultor(['comissao_percentual' => null]))
            ->assertSessionHasNoErrors();

        $this->assertEquals(0, (float) User::where('email', 'novo.consultor@teste.com')->value('comissao_percentual'));
    }

    public function test_valida_campos_do_consultor(): void
    {
        $existente = $this->consultor();

        $this->actingAs($this->admin())
            ->post(route('admin.usuarios.consultores.store'), $this->dadosConsultor([
                'email' => $existente->email, 'password_confirmation' => 'outra-senha',
                'comissao_percentual' => 150,
            ]))
            ->assertSessionHasErrors(['email', 'password', 'comissao_percentual']);
    }

    public function test_atualiza_consultor_sem_trocar_senha_quando_vazia(): void
    {
        $consultor = $this->consultor();
        $hashAntigo = $consultor->password;

        $this->actingAs($this->admin())
            ->put(route('admin.usuarios.consultores.update', $consultor), [
                'name' => 'Nome Novo', 'email' => $consultor->email, 'password' => '',
                'comissao_percentual' => 3, 'status' => false,
            ])
            ->assertSessionHasNoErrors();

        $consultor->refresh();
        $this->assertSame('Nome Novo', $consultor->name);
        $this->assertFalse($consultor->status);
        $this->assertSame($hashAntigo, $consultor->password);
    }

    public function test_rotas_de_consultor_nao_operam_sobre_admin(): void
    {
        $admin = $this->admin();
        $outroAdmin = $this->admin();

        $this->actingAs($admin)->get(route('admin.usuarios.consultores.edit', $outroAdmin))->assertNotFound();
        $this->actingAs($admin)->put(route('admin.usuarios.consultores.update', $outroAdmin), $this->dadosConsultor())->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.usuarios.consultores.destroy', $admin))->assertNotFound();

        $this->assertNotNull($admin->fresh());
    }

    public function test_nao_exclui_consultor_com_clientes_ou_orcamentos(): void
    {
        $consultor = $this->consultor();
        $this->cliente($consultor);

        $this->actingAs($this->admin())
            ->delete(route('admin.usuarios.consultores.destroy', $consultor))
            ->assertSessionHas('error');

        $this->assertNotNull($consultor->fresh());
    }

    public function test_exclui_consultor_sem_vinculos(): void
    {
        $consultor = $this->consultor();

        $this->actingAs($this->admin())
            ->delete(route('admin.usuarios.consultores.destroy', $consultor))
            ->assertRedirect(route('admin.usuarios.consultores.index'));

        $this->assertNull($consultor->fresh());
    }

    public function test_listagem_de_consultores_filtra_por_busca_e_status(): void
    {
        $this->consultor(['name' => 'Ana Ativa', 'status' => true]);
        $this->consultor(['name' => 'Bruno Inativo', 'status' => false]);
        $admin = $this->admin(['name' => 'Ana Admin']);

        $this->actingAs($admin)->get(route('admin.usuarios.consultores.index', ['search' => 'Ana']))
            ->assertInertia(fn ($page) => $page->has('consultores.data', 1)->where('consultores.data.0.name', 'Ana Ativa'));
        $this->actingAs($admin)->get(route('admin.usuarios.consultores.index', ['status' => '0']))
            ->assertInertia(fn ($page) => $page->has('consultores.data', 1)->where('consultores.data.0.name', 'Bruno Inativo'));
    }

    // ── Admins ───────────────────────────────────────────────────────────

    public function test_admin_cria_outro_admin(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.usuarios.admins.store'), [
                'name' => 'Admin Dois', 'email' => 'admin.dois@teste.com',
                'password' => 'senha-forte-123', 'password_confirmation' => 'senha-forte-123', 'status' => true,
            ])
            ->assertRedirect(route('admin.usuarios.admins.index'));

        $this->assertSame('admin', User::where('email', 'admin.dois@teste.com')->value('tipo'));
    }

    public function test_admin_nao_exclui_nem_desativa_a_si_mesmo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.usuarios.admins.destroy', $admin))->assertSessionHas('error');
        $this->actingAs($admin)
            ->put(route('admin.usuarios.admins.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'status' => false])
            ->assertSessionHasErrors('status');

        $this->assertTrue($admin->fresh()->status);
    }

    public function test_admin_exclui_outro_admin(): void
    {
        $outro = $this->admin();

        $this->actingAs($this->admin())->delete(route('admin.usuarios.admins.destroy', $outro))
            ->assertRedirect(route('admin.usuarios.admins.index'));

        $this->assertNull($outro->fresh());
    }

    public function test_rotas_de_admin_nao_operam_sobre_consultor(): void
    {
        $consultor = $this->consultor();

        $this->actingAs($this->admin())->delete(route('admin.usuarios.admins.destroy', $consultor))->assertNotFound();
        $this->assertNotNull($consultor->fresh());
    }
}
