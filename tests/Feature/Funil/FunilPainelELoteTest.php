<?php

namespace Tests\Feature\Funil;

use App\Models\FunilEtapa;
use App\Models\Orcamento;
use App\Models\OrcamentoHistorico;
use App\Services\Funil\FunilService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\Concerns\CriaFunil;
use Tests\TestCase;

/**
 * Onda 2 do funil (docs/funil-de-vendas.md, seção 22.4): painel lateral, contato rápido,
 * filtros novos, reatribuição de consultor e ações em lote.
 */
class FunilPainelELoteTest extends TestCase
{
    use CriaDados, CriaFunil, RefreshDatabase;

    // ── Painel lateral ─────────────────────────────────────────────────────

    public function test_painel_do_card_respeita_o_dono(): void
    {
        $dono = $this->consultor();
        $orcamento = $this->orcamento($dono, ['funil_etapa_id' => $this->etapaAberta(2)->id]);
        $this->itemKit($orcamento);
        app(FunilService::class)->registrarContato($orcamento, $dono, 'Cliente pediu desconto.', now()->addDay());

        $this->getJson(route('consultor.funil.show', $orcamento))->assertUnauthorized();
        $this->actingAs($this->consultor())->getJson(route('consultor.funil.show', $orcamento))->assertForbidden();
        $this->actingAs($this->admin())->getJson(route('admin.funil.show', $orcamento))->assertOk();

        $this->actingAs($dono)->getJson(route('consultor.funil.show', $orcamento))
            ->assertOk()
            ->assertJsonPath('id', $orcamento->id)
            ->assertJsonPath('etapa.id', $this->etapaAberta(2)->id)
            ->assertJsonPath('itens.0.descricao', 'Kit 5kWp')
            ->assertJsonPath('historico.0.tipo', 'contato')
            ->assertJsonPath('historico.0.usuario', $dono->name)
            ->assertJsonPath('saude', 'em_dia');
    }

    public function test_contatos_do_cliente_para_ligar_e_whatsapp(): void
    {
        $consultor = $this->consultor();
        $contatos = fn (array $attrs) => FunilService::contatos($this->cliente($consultor, $attrs));

        $this->assertSame(['telefone' => '11987654321', 'whatsapp' => '5511987654321'], $contatos(['celular' => '(11) 98765-4321', 'telefone' => '(11) 3333-4444']));
        $this->assertSame(['telefone' => '1133334444', 'whatsapp' => '551133334444'], $contatos(['telefone' => '(11) 3333-4444']));
        $this->assertSame(['telefone' => '5511987654321', 'whatsapp' => '5511987654321'], $contatos(['celular' => '+55 11 98765-4321']));
        $this->assertSame(['telefone' => null, 'whatsapp' => null], $contatos(['celular' => '9876']));
        $this->assertSame(['telefone' => null, 'whatsapp' => null], FunilService::contatos(null));
    }

    public function test_card_do_quadro_traz_o_whatsapp(): void
    {
        $consultor = $this->consultor();
        $cliente = $this->cliente($consultor, ['celular' => '(85) 99999-0000']);
        $this->orcamento($consultor, ['cliente_id' => $cliente->id, 'funil_etapa_id' => $this->etapaAberta()->id]);

        $this->actingAs($consultor)->get(route('consultor.funil.index'))
            ->assertInertia(fn ($page) => $page->where('colunas.0.cards.0.whatsapp', '5585999990000'));
    }

    // ── Filtros e ordenação ───────────────────────────────────────────────

    public function test_filtro_sem_proximo_passo_e_contagem_no_resumo(): void
    {
        $consultor = $this->consultor();
        $etapa = $this->etapaAberta();
        $semPasso = $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id]);
        $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id, 'proximo_contato_em' => now()->addDay()]);
        $this->orcamento($consultor); // caixa de entrada: ainda não tem passo nenhum, não conta
        $this->orcamento($consultor, ['status' => 'aprovando']); // aguardando o admin, não conta

        $this->actingAs($consultor)->get(route('consultor.funil.index'))
            ->assertInertia(fn ($page) => $page->where('resumo.sem_passo', 1));

        $quadro = app(FunilService::class)->quadro($consultor, ['sem_passo' => true]);
        $ids = collect($quadro['colunas'])->flatMap(fn ($c) => $c['cards'])->pluck('id')->all();
        $this->assertSame([$semPasso->id], $ids);
        $this->assertSame([], $quadro['caixa']);
    }

    public function test_filtro_prazo_estourado(): void
    {
        $consultor = $this->consultor();
        $etapa = $this->etapaAberta(1); // SLA 2 dias
        $estourado = $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id]);
        $estourado->forceFill(['etapa_entrou_em' => now()->subDays(3)])->saveQuietly();
        $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id]);
        $this->orcamento($consultor, ['status' => 'aprovado']);
        $this->orcamento($consultor);

        $quadro = app(FunilService::class)->quadro($consultor, ['sla' => true]);
        $ids = collect($quadro['colunas'])->flatMap(fn ($c) => $c['cards'])->pluck('id')->all();

        $this->assertSame([$estourado->id], $ids);
        $this->assertSame([], $quadro['caixa']);
    }

    public function test_ordenacao_por_valor_e_por_mais_antigo(): void
    {
        $consultor = $this->consultor();
        $etapa = $this->etapaAberta();
        $barato = $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id, 'preco_total' => 5000]);
        $caro = $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id, 'preco_total' => 90000]);
        $barato->forceFill(['etapa_entrou_em' => now()->subDays(4)])->saveQuietly();

        $ordem = fn (?string $o) => collect(app(FunilService::class)->quadro($consultor, ['ordem' => $o])['colunas'][0]['cards'])->pluck('id')->all();

        $this->assertSame([$caro->id, $barato->id], $ordem('valor'));
        $this->assertSame([$barato->id, $caro->id], $ordem('antigo'));

        $this->actingAs($consultor)->get(route('consultor.funil.index', ['ordem' => 'valor']))
            ->assertInertia(fn ($page) => $page->where('colunas.0.cards.0.id', $caro->id)->where('filtros.ordem', 'valor'));
    }

    // ── Reatribuir consultor ──────────────────────────────────────────────

    public function test_admin_reatribui_negociacao_com_comissao_do_novo_consultor(): void
    {
        $antigo = $this->consultor(['comissao_percentual' => 5]);
        $novo = $this->consultor(['comissao_percentual' => 8]);
        $orcamento = $this->orcamento($antigo, ['funil_etapa_id' => $this->etapaAberta()->id]);
        $item = $this->itemKit($orcamento, ['comissao_percentual' => 5]);

        $this->actingAs($this->admin())
            ->post(route('admin.funil.reatribuir', $orcamento), ['consultor_id' => $novo->id])
            ->assertSessionHas('success');

        $this->assertSame($novo->id, $orcamento->fresh()->consultor_id);
        $this->assertEquals(8, $item->fresh()->comissao_percentual);
        $this->assertDatabaseHas('orcamento_historicos', [
            'orcamento_id' => $orcamento->id, 'tipo' => 'responsavel', 'mensagem' => "Responsável: {$antigo->name} → {$novo->name}.",
        ]);

        // Mesmo consultor de novo: nada muda e nada vai para a linha do tempo.
        $this->actingAs($this->admin())
            ->post(route('admin.funil.reatribuir', $orcamento), ['consultor_id' => $novo->id])
            ->assertSessionHas('info', 'Este consultor já é o responsável.');
        $this->assertSame(1, OrcamentoHistorico::where('tipo', 'responsavel')->count());
    }

    public function test_reatribuir_e_recusado_fora_da_negociacao_ou_para_quem_nao_e_consultor_ativo(): void
    {
        $admin = $this->admin();
        $dono = $this->consultor();
        $inativo = $this->consultor(['status' => false]);
        $emAprovacao = $this->orcamento($dono, ['status' => 'aprovando']);
        $aberto = $this->orcamento($dono, ['funil_etapa_id' => $this->etapaAberta()->id]);

        $this->actingAs($admin)->post(route('admin.funil.reatribuir', $emAprovacao), ['consultor_id' => $this->consultor()->id])
            ->assertSessionHas('error', 'Só negociações em andamento podem ser reatribuídas.');
        $this->actingAs($admin)->post(route('admin.funil.reatribuir', $aberto), ['consultor_id' => $inativo->id])
            ->assertSessionHas('error', 'Escolha um consultor ativo.');
        $this->actingAs($admin)->post(route('admin.funil.reatribuir', $aberto), ['consultor_id' => $admin->id])
            ->assertSessionHasErrors('consultor_id');

        // Consultor não chega nem à rota (área admin).
        $this->actingAs($dono)->post(route('admin.funil.reatribuir', $aberto), ['consultor_id' => $this->consultor()->id])->assertForbidden();
        $this->assertSame($dono->id, $aberto->fresh()->consultor_id);
        $this->assertSame($dono->id, $emAprovacao->fresh()->consultor_id);
    }

    // ── Ações em lote ──────────────────────────────────────────────────────

    public function test_lote_move_so_negociacoes_em_andamento(): void
    {
        $consultor = $this->consultor();
        $destino = $this->etapaAberta(3);
        $naCaixa = $this->orcamento($consultor);
        $aberto = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta(1)->id]);
        $emAprovacao = $this->orcamento($consultor, ['status' => 'aprovando', 'funil_etapa_id' => $this->etapaAberta(1)->id]);

        $this->actingAs($this->admin())
            ->post(route('admin.funil.lote'), ['ids' => [$naCaixa->id, $aberto->id, $emAprovacao->id], 'acao' => 'mover', 'etapa_id' => $destino->id])
            ->assertSessionHas('warning', "2 orçamentos atualizados. 1 recusado — ex.: #{$emAprovacao->id}: Só negociações em andamento podem ser movidas em lote.");

        $this->assertSame($destino->id, $naCaixa->fresh()->funil_etapa_id);
        $this->assertSame($destino->id, $aberto->fresh()->funil_etapa_id);
        $this->assertSame('aprovando', $emAprovacao->fresh()->status); // não foi reprovado
    }

    public function test_lote_perde_e_reatribui(): void
    {
        $antigo = $this->consultor();
        $novo = $this->consultor();
        $a = $this->orcamento($antigo, ['funil_etapa_id' => $this->etapaAberta()->id]);
        $b = $this->orcamento($antigo);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.funil.lote'), ['ids' => [$a->id, $b->id], 'acao' => 'reatribuir', 'consultor_id' => $novo->id])
            ->assertSessionHas('success', '2 orçamentos atualizados.');
        $this->assertSame([$novo->id, $novo->id], [$a->fresh()->consultor_id, $b->fresh()->consultor_id]);

        $motivo = $this->motivo();
        $this->actingAs($admin)
            ->post(route('admin.funil.lote'), ['ids' => [$a->id, $b->id], 'acao' => 'perder', 'motivo_perda_id' => $motivo->id])
            ->assertSessionHas('success');
        $this->assertSame([$motivo->id, $motivo->id], [$a->fresh()->motivo_perda_id, $b->fresh()->motivo_perda_id]);
        $this->assertSame(2, OrcamentoHistorico::where('tipo', 'perda')->count());
    }

    public function test_lote_valida_acao_e_destino_e_e_exclusivo_do_admin(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta()->id]);
        $admin = $this->admin();

        // Aprovar em lote não existe: só etapas abertas são destino.
        $this->actingAs($admin)->post(route('admin.funil.lote'), [
            'ids' => [$orcamento->id], 'acao' => 'mover', 'etapa_id' => $this->etapaSistema(FunilEtapa::GANHO)->id,
        ])->assertSessionHasErrors('etapa_id');
        $this->actingAs($admin)->post(route('admin.funil.lote'), ['ids' => [], 'acao' => 'mover'])->assertSessionHasErrors('ids');
        $this->actingAs($admin)->post(route('admin.funil.lote'), ['ids' => [$orcamento->id], 'acao' => 'excluir'])->assertSessionHasErrors('acao');
        $this->actingAs($admin)->post(route('admin.funil.lote'), ['ids' => [$orcamento->id], 'acao' => 'perder'])->assertSessionHasErrors('motivo_perda_id');

        $this->actingAs($consultor)->post(route('admin.funil.lote'), [
            'ids' => [$orcamento->id], 'acao' => 'mover', 'etapa_id' => $this->etapaAberta(2)->id,
        ])->assertForbidden();
        $this->assertSame('novo', Orcamento::find($orcamento->id)->status);
        $this->assertSame($this->etapaAberta()->id, $orcamento->fresh()->funil_etapa_id);
    }

    public function test_servico_de_lote_recusa_acao_desconhecida_e_quem_nao_e_admin(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta()->id]);
        $servico = app(FunilService::class);

        $resultado = $servico->lote([$orcamento->id], 'excluir', [], $this->admin());
        $this->assertSame(['feitos' => 0, 'recusados' => [$orcamento->id => 'Ação em lote desconhecida: excluir.']], $resultado);

        $this->expectExceptionMessage('Só o administrador executa ações em lote.');
        $servico->lote([$orcamento->id], 'perder', ['motivo_perda_id' => $this->motivo()->id], $consultor);
    }
}
