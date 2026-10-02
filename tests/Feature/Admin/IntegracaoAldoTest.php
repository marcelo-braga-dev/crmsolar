<?php

namespace Tests\Feature\Admin;

use App\Models\IntegracaoHistorico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Integração Aldo aguardando especificação do feed: a tela informa e o backend não executa nada.
 */
class IntegracaoAldoTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    public function test_tela_informa_que_a_integracao_nao_esta_disponivel(): void
    {
        $this->actingAs($this->admin())->get(route('admin.integracoes.aldo'))
            ->assertInertia(fn ($page) => $page->component('Admin/Integracoes/Aldo/Index')->where('disponivel', false));
    }

    public function test_executar_integracao_avisa_e_nao_registra_historico(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.integracoes.aldo.integrar'))
            ->assertSessionHas('warning');

        $this->assertSame(0, IntegracaoHistorico::count());
    }
}
