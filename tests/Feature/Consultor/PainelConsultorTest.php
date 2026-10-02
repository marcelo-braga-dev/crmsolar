<?php

namespace Tests\Feature\Consultor;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Dashboard, Financeiro e Perfil (Perfil testado para os dois papéis).
 */
class PainelConsultorTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    public function test_dashboard_mostra_somente_numeros_do_consultor(): void
    {
        $eu = $this->consultor();
        $this->orcamento($eu, ['status' => 'aprovado', 'preco_total' => 12000]);
        $this->orcamento($eu, ['status' => 'aprovando']);
        $this->lead($eu, ['status' => 'contatado']);
        $outro = $this->consultor();
        $this->orcamento($outro, ['status' => 'aprovado', 'preco_total' => 99999]);

        $this->actingAs($eu)->get(route('consultor.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.clientes', 2)
                ->where('stats.orcamentos_mes', 2)
                ->where('stats.valor_aprovado_mes', 12000)
                ->where('stats.em_aprovacao', 1)
                ->where('stats.aprovados', 1)
                ->where('stats.leads_abertos', 1)
                ->has('recentes', 2)
                ->etc());
    }

    public function test_financeiro_soma_comissao_dos_orcamentos_fechados(): void
    {
        $eu = $this->consultor();
        // 5% de 10.000 + 5% de 20.000 = 1.500; o orçamento "novo" não conta.
        $this->itemKit($this->orcamento($eu, ['status' => 'aprovado']), ['preco_venda_total' => 10000, 'comissao_percentual' => 5]);
        $this->itemKit($this->orcamento($eu, ['status' => 'finalizado']), ['preco_venda_total' => 20000, 'comissao_percentual' => 5]);
        $this->itemKit($this->orcamento($eu, ['status' => 'novo']), ['preco_venda_total' => 50000, 'comissao_percentual' => 5]);
        $this->itemKit($this->orcamento($this->consultor(), ['status' => 'aprovado']), ['preco_venda_total' => 80000]);

        $this->actingAs($eu)->get(route('consultor.financeiro'))
            ->assertInertia(fn ($page) => $page
                ->has('orcamentos', 2)
                ->where('total_comissoes', fn ($v) => abs((float) $v - 1500) < 0.01));
    }

    /** @return array<string, array{string}> */
    public static function papeis(): array
    {
        return ['admin' => ['admin'], 'consultor' => ['consultor']];
    }

    #[DataProvider('papeis')]
    public function test_atualiza_perfil(string $papel): void
    {
        $user = $this->{$papel}();

        $this->actingAs($user)->put(route("{$papel}.perfil.update"), [
            'name' => 'Nome Atualizado', 'email' => 'novo.'.$papel.'@teste.com', 'celular' => '(85) 98888-0000',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Nome Atualizado', $user->fresh()->name);
    }

    #[DataProvider('papeis')]
    public function test_perfil_nao_aceita_email_de_outro_usuario(string $papel): void
    {
        $existente = $this->consultor();

        $this->actingAs($this->{$papel}())
            ->put(route("{$papel}.perfil.update"), ['name' => 'X', 'email' => $existente->email])
            ->assertSessionHasErrors('email');
    }

    #[DataProvider('papeis')]
    public function test_troca_senha_exige_senha_atual_correta(string $papel): void
    {
        $user = $this->{$papel}(['password' => Hash::make('senha-antiga-123')]);

        $this->actingAs($user)->put(route("{$papel}.perfil.senha.update"), [
            'senha_atual' => 'errada', 'password' => 'senha-nova-123', 'password_confirmation' => 'senha-nova-123',
        ])->assertSessionHasErrors('senha_atual');

        $this->actingAs($user)->put(route("{$papel}.perfil.senha.update"), [
            'senha_atual' => 'senha-antiga-123', 'password' => 'senha-nova-123', 'password_confirmation' => 'senha-nova-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('senha-nova-123', $user->fresh()->password));
    }
}
