<?php

namespace Tests\Feature\Consultor;

use App\Models\OrcamentoInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

class OrcamentosTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    public function test_listagem_mostra_apenas_orcamentos_do_consultor_com_stats(): void
    {
        $eu = $this->consultor();
        $this->orcamento($eu, ['status' => 'novo', 'preco_total' => 1000]);
        $this->orcamento($eu, ['status' => 'aprovado', 'preco_total' => 25000]);
        $this->orcamento($this->consultor());

        $this->actingAs($eu)->get(route('consultor.orcamentos.index'))
            ->assertInertia(fn ($page) => $page
                ->has('orcamentos.data', 2)
                ->where('stats.total', 2)
                ->where('stats.aprovados', 1)
                ->where('stats.valor_total', 25000));
    }

    public function test_listagem_filtra_por_status_e_busca_por_cliente(): void
    {
        $eu = $this->consultor();
        $this->orcamento($eu, ['status' => 'aprovado', 'cliente_id' => $this->cliente($eu, ['nome' => 'Joana Prado'])->id]);
        $this->orcamento($eu, ['status' => 'novo']);

        $this->actingAs($eu)->get(route('consultor.orcamentos.index', ['status' => 'aprovado']))
            ->assertInertia(fn ($page) => $page->has('orcamentos.data', 1));
        $this->actingAs($eu)->get(route('consultor.orcamentos.index', ['search' => 'Joana']))
            ->assertInertia(fn ($page) => $page->has('orcamentos.data', 1));
    }

    public function test_create_e_store_legados_redirecionam_para_o_dimensionamento(): void
    {
        $eu = $this->consultor();

        $this->actingAs($eu)->get(route('consultor.orcamentos.create'))->assertRedirect(route('consultor.dimensionamento.convencional'));
        $this->actingAs($eu)->post(route('consultor.orcamentos.store'))->assertRedirect(route('consultor.dimensionamento.convencional'));
    }

    public function test_consultor_nao_ve_nem_altera_orcamento_alheio(): void
    {
        $alheio = $this->orcamento($this->consultor());
        $eu = $this->consultor();

        $this->actingAs($eu)->get(route('consultor.orcamentos.show', $alheio))->assertForbidden();
        $this->actingAs($eu)->get(route('consultor.orcamentos.edit', $alheio))->assertForbidden();
        $this->actingAs($eu)->put(route('consultor.orcamentos.update', $alheio), ['status' => 'aprovando'])->assertForbidden();
        $this->actingAs($eu)->delete(route('consultor.orcamentos.destroy', $alheio))->assertForbidden();
    }

    public function test_envia_orcamento_novo_para_aprovacao_com_historico(): void
    {
        $eu = $this->consultor();
        $orcamento = $this->orcamento($eu);

        $this->actingAs($eu)->put(route('consultor.orcamentos.update', $orcamento), ['status' => 'aprovando'])
            ->assertRedirect(route('consultor.orcamentos.show', $orcamento));

        $this->assertSame('aprovando', $orcamento->fresh()->status);
        $this->assertDatabaseHas('orcamento_historicos', ['orcamento_id' => $orcamento->id, 'status' => 'aprovando']);
    }

    public function test_reenvia_orcamento_reprovado_para_aprovacao(): void
    {
        $eu = $this->consultor();
        $orcamento = $this->orcamento($eu, ['status' => 'aprovacao_reprovada']);

        $this->actingAs($eu)->put(route('consultor.orcamentos.update', $orcamento), ['status' => 'aprovando']);

        $this->assertSame('aprovando', $orcamento->fresh()->status);
    }

    /** @return array<string, array{string}> */
    public static function statusQueNaoVoltamParaAprovacao(): array
    {
        return ['aprovando' => ['aprovando'], 'aprovado' => ['aprovado'], 'instalando' => ['instalando'], 'finalizado' => ['finalizado']];
    }

    #[DataProvider('statusQueNaoVoltamParaAprovacao')]
    public function test_nao_reenvia_para_aprovacao_a_partir_de(string $status): void
    {
        $eu = $this->consultor();
        $orcamento = $this->orcamento($eu, ['status' => $status]);

        $this->actingAs($eu)->put(route('consultor.orcamentos.update', $orcamento), ['status' => 'aprovando'])
            ->assertSessionHas('error');

        $this->assertSame($status, $orcamento->fresh()->status);
    }

    public function test_consultor_nao_define_outro_status_alem_de_aprovando(): void
    {
        $eu = $this->consultor();
        $orcamento = $this->orcamento($eu);

        $this->actingAs($eu)->put(route('consultor.orcamentos.update', $orcamento), ['status' => 'aprovado'])
            ->assertSessionHasErrors('status');
    }

    public function test_enviar_para_aprovacao_nao_apaga_anotacoes(): void
    {
        $eu = $this->consultor();
        $orcamento = $this->orcamento($eu, ['anotacoes' => 'Cliente prefere boleto']);

        $this->actingAs($eu)->put(route('consultor.orcamentos.update', $orcamento), ['status' => 'aprovando']);

        $this->assertSame('Cliente prefere boleto', $orcamento->fresh()->anotacoes);
    }

    public function test_atualiza_anotacoes_e_anotacoes_tecnicas(): void
    {
        $eu = $this->consultor();
        $orcamento = $this->orcamento($eu);
        OrcamentoInfo::create(['orcamento_id' => $orcamento->id]);

        $this->actingAs($eu)->put(route('consultor.orcamentos.update', $orcamento), [
            'anotacoes' => 'Nova anotação', 'anotacoes_tecnicas' => 'Telhado com sombra parcial',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Nova anotação', $orcamento->fresh()->anotacoes);
        $this->assertSame('Telhado com sombra parcial', $orcamento->info->fresh()->anotacoes_tecnicas);
    }

    public function test_orcamento_bloqueado_nao_pode_ser_editado(): void
    {
        $eu = $this->consultor();
        $orcamento = $this->orcamento($eu);
        OrcamentoInfo::create(['orcamento_id' => $orcamento->id, 'bloquear_edicao' => true]);

        $this->actingAs($eu)->get(route('consultor.orcamentos.edit', $orcamento))->assertForbidden();
        $this->actingAs($eu)->put(route('consultor.orcamentos.update', $orcamento), ['anotacoes' => 'x'])->assertForbidden();
    }

    public function test_exclui_apenas_orcamento_novo(): void
    {
        $eu = $this->consultor();
        $novo = $this->orcamento($eu);
        $aprovado = $this->orcamento($eu, ['status' => 'aprovado']);

        $this->actingAs($eu)->delete(route('consultor.orcamentos.destroy', $aprovado))->assertForbidden();
        $this->actingAs($eu)->delete(route('consultor.orcamentos.destroy', $novo))->assertRedirect(route('consultor.orcamentos.index'));

        $this->assertSoftDeleted($novo);
        $this->assertNotSoftDeleted($aprovado);
    }

    public function test_busca_de_produtos_retorna_apenas_ativos(): void
    {
        $this->produto(['nome' => 'Cabo Solar Ativo']);
        $this->produto(['nome' => 'Cabo Solar Inativo', 'ativo' => false]);
        $this->produto(['nome' => 'Cabo Solar Fora do Fornecedor', 'ativo_fornecedor' => false]);

        $this->actingAs($this->consultor())
            ->getJson(route('consultor.orcamentos.produtos.buscar', ['q' => 'Cabo']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.nome', 'Cabo Solar Ativo');
    }
}
