<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioInativoTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_inativo_nao_consegue_fazer_login(): void
    {
        $user = User::factory()->create(['tipo' => 'consultor', 'status' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_ativo_faz_login_normalmente(): void
    {
        $user = User::factory()->create(['tipo' => 'consultor']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_sessao_de_usuario_desativado_apos_login_e_encerrada(): void
    {
        $user = User::factory()->create(['tipo' => 'consultor']);
        $this->actingAs($user)->get(route('consultor.dashboard'))->assertOk();

        $user->update(['status' => false]);

        $this->actingAs($user->fresh())
            ->get(route('consultor.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
