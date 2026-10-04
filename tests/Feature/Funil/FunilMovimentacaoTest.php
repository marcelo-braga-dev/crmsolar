<?php

namespace Tests\Feature\Funil;

use App\Models\FunilEtapa;
use App\Models\Orcamento;
use App\Models\OrcamentoHistorico;
use App\Models\OrcamentoInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\Concerns\CriaFunil;
use Tests\TestCase;

/**
 * Movimentações do quadro (docs/funil-de-vendas.md, seção 7): quem pode mover o quê,
 * efeitos no status/histórico e proteções contra conflito.
 */
class FunilMovimentacaoTest extends TestCase
{
    use CriaDados, CriaFunil, RefreshDatabase;

    private function mover(User $quem, Orcamento $orcamento, FunilEtapa $destino, array $extra = [])
    {
        $area = $quem->isAdmin() ? 'admin' : 'consultor';

        return $this->actingAs($quem)->post(route("{$area}.funil.mover", $orcamento), $extra + [
            'etapa_id' => $destino->id,
            'origem' => $this->origemDe($orcamento),
        ]);
    }

    // ── Entre etapas abertas ──────────────────────────────────────────────

    public function test_iniciar_atendimento_tira_da_caixa_e_agenda_contato(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor);
        $orcamento->forceFill(['etapa_entrou_em' => now()->subDays(3)])->saveQuietly();
        $amanha = now()->addDay()->setTime(10, 0);

        $this->mover($consultor, $orcamento, $this->etapaAberta(), ['proximo_contato_em' => $amanha->toDateTimeString()])
            ->assertSessionHasNoErrors()->assertSessionMissing('error');

        $orcamento->refresh();
        $this->assertSame($this->etapaAberta()->id, $orcamento->funil_etapa_id);
        $this->assertSame('novo', $orcamento->status);
        $this->assertTrue($orcamento->etapa_entrou_em->isToday());
        $this->assertEquals($amanha, $orcamento->proximo_contato_em);
        $this->assertDatabaseHas('orcamento_historicos', [
            'orcamento_id' => $orcamento->id, 'tipo' => 'etapa', 'status' => null,
            'mensagem' => 'Etapa: Caixa de entrada → Primeiro contato.',
        ]);
    }

    public function test_mover_entre_etapas_abertas(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta(1)->id]);

        $this->mover($consultor, $orcamento, $this->etapaAberta(4))->assertSessionMissing('error');

        $this->assertSame($this->etapaAberta(4)->id, $orcamento->fresh()->funil_etapa_id);
        $this->assertSame(1, OrcamentoHistorico::where('tipo', 'etapa')->count());
    }

    public function test_consultor_nao_move_orcamento_de_outro_e_admin_move_qualquer(): void
    {
        $orcamento = $this->orcamento($this->consultor(), ['funil_etapa_id' => $this->etapaAberta(1)->id]);

        $this->mover($this->consultor(), $orcamento, $this->etapaAberta(2))->assertForbidden();
        $this->assertSame($this->etapaAberta(1)->id, $orcamento->fresh()->funil_etapa_id);

        $this->mover($this->admin(), $orcamento, $this->etapaAberta(2))->assertSessionMissing('error');
        $this->assertSame($this->etapaAberta(2)->id, $orcamento->fresh()->funil_etapa_id);
    }

    public function test_visitante_vai_para_o_login(): void
    {
        $orcamento = $this->orcamento($this->consultor());

        $this->post(route('consultor.funil.mover', $orcamento), ['etapa_id' => $this->etapaAberta()->id, 'origem' => 'caixa'])
            ->assertRedirect(route('login'));
    }

    public function test_card_movido_por_outra_pessoa_e_recusado(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta(2)->id]);

        // O usuário ainda vê o card na caixa de entrada, mas ele já está na etapa 2.
        $this->actingAs($consultor)->post(route('consultor.funil.mover', $orcamento), [
            'etapa_id' => $this->etapaAberta(3)->id, 'origem' => 'caixa',
        ])->assertSessionHas('error', 'Este orçamento foi movido por outra pessoa. O quadro foi atualizado.');

        $this->assertSame($this->etapaAberta(2)->id, $orcamento->fresh()->funil_etapa_id);
        $this->assertSame(0, OrcamentoHistorico::count());
    }

    public function test_nao_move_para_etapa_inativa_nem_de_volta_para_a_caixa(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta(1)->id]);
        $inativa = $this->etapaAberta(5);
        $inativa->update(['ativa' => false]);

        $this->mover($consultor, $orcamento, $inativa)->assertSessionHas('error', 'Etapa de destino inválida.');

        $this->actingAs($consultor)->post(route('consultor.funil.mover', $orcamento), [
            'etapa_id' => 'caixa', 'origem' => $this->origemDe($orcamento),
        ])->assertSessionHasErrors('etapa_id');
    }

    public function test_data_de_proximo_contato_no_passado_e_recusada(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor);

        $this->mover($consultor, $orcamento, $this->etapaAberta(), ['proximo_contato_em' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('proximo_contato_em');
    }

    // ── Aprovação ─────────────────────────────────────────────────────────

    public function test_soltar_em_aprovacao_envia_para_aprovacao(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta(6)->id]);

        $this->mover($consultor, $orcamento, $this->etapaSistema(FunilEtapa::APROVACAO))->assertSessionMissing('error');

        $orcamento->refresh();
        $this->assertSame('aprovando', $orcamento->status);
        $this->assertSame($this->etapaAberta(6)->id, $orcamento->funil_etapa_id); // lembra de onde veio
        $this->assertDatabaseHas('orcamento_historicos', ['orcamento_id' => $orcamento->id, 'tipo' => 'status', 'status' => 'aprovando']);
    }

    public function test_orcamento_da_caixa_enviado_para_aprovacao_guarda_a_primeira_etapa(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor);

        $this->mover($consultor, $orcamento, $this->etapaSistema(FunilEtapa::APROVACAO))->assertSessionMissing('error');

        $this->assertSame($this->etapaAberta(1)->id, $orcamento->fresh()->funil_etapa_id);
    }

    public function test_orcamento_bloqueado_nao_vai_para_aprovacao(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta()->id]);
        OrcamentoInfo::create(['orcamento_id' => $orcamento->id, 'bloquear_edicao' => true, 'tensao' => 220, 'orientacao' => 'norte']);

        $this->mover($consultor, $orcamento, $this->etapaSistema(FunilEtapa::APROVACAO))
            ->assertSessionHas('error', 'Orçamento bloqueado para edição.');
        $this->assertSame('novo', $orcamento->fresh()->status);
    }

    public function test_so_admin_aprova_e_so_a_partir_de_em_aprovacao(): void
    {
        $consultor = $this->consultor();
        $admin = $this->admin();
        $ganho = $this->etapaSistema(FunilEtapa::GANHO);
        $emAprovacao = $this->orcamento($consultor, ['status' => 'aprovando', 'funil_etapa_id' => $this->etapaAberta()->id]);
        $emNegociacao = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta()->id]);

        $this->mover($consultor, $emAprovacao, $ganho)->assertSessionHas('error', 'Só o administrador aprova orçamentos.');
        $this->mover($admin, $emNegociacao, $ganho)->assertSessionHas('error', 'Para fechar a venda, envie o orçamento para aprovação primeiro.');
        $this->assertSame('novo', $emNegociacao->fresh()->status);

        $this->mover($admin, $emAprovacao, $ganho)->assertSessionMissing('error');
        $this->assertSame('aprovado', $emAprovacao->fresh()->status);
    }

    public function test_admin_reprova_devolvendo_para_uma_etapa_e_consultor_nao(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['status' => 'aprovando', 'funil_etapa_id' => $this->etapaAberta(6)->id]);

        $this->mover($consultor, $orcamento, $this->etapaAberta(4))
            ->assertSessionHas('error', 'Só o administrador pode reprovar um orçamento em aprovação.');
        $this->assertSame('aprovando', $orcamento->fresh()->status);

        $this->mover($this->admin(), $orcamento, $this->etapaAberta(4))->assertSessionMissing('error');

        $orcamento->refresh();
        $this->assertSame('aprovacao_reprovada', $orcamento->status);
        $this->assertSame($this->etapaAberta(4)->id, $orcamento->funil_etapa_id);
        $this->assertDatabaseHas('orcamento_historicos', ['orcamento_id' => $orcamento->id, 'status' => 'aprovacao_reprovada']);
    }

    public function test_venda_ganha_nao_volta_pelo_quadro(): void
    {
        $orcamento = $this->orcamento($this->consultor(), ['status' => 'aprovado', 'funil_etapa_id' => $this->etapaAberta()->id]);

        $this->mover($this->admin(), $orcamento, $this->etapaAberta(3))
            ->assertSessionHas('error', 'Venda ganha não volta pelo quadro. Use a tela do orçamento.');
        $this->assertSame('aprovado', $orcamento->fresh()->status);
    }

    // ── Perda ─────────────────────────────────────────────────────────────

    public function test_perdido_so_com_motivo(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta()->id]);

        $this->mover($consultor, $orcamento, $this->etapaSistema(FunilEtapa::PERDIDO))
            ->assertSessionHas('error', 'Para marcar como perdido, informe o motivo da perda.');

        $this->actingAs($consultor)->post(route('consultor.funil.perder', $orcamento), ['origem' => $this->origemDe($orcamento)])
            ->assertSessionHasErrors('motivo_perda_id');

        $this->assertNull($orcamento->fresh()->perdido_em);
    }

    public function test_marcar_como_perdido(): void
    {
        $consultor = $this->consultor();
        $motivo = $this->motivo();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta()->id, 'proximo_contato_em' => now()->addDay()]);

        $this->actingAs($consultor)->post(route('consultor.funil.perder', $orcamento), [
            'motivo_perda_id' => $motivo->id, 'observacao' => 'Vai esperar o 13º.', 'origem' => $this->origemDe($orcamento),
        ])->assertSessionHas('success');

        $orcamento->refresh();
        $this->assertNotNull($orcamento->perdido_em);
        $this->assertSame($motivo->id, $orcamento->motivo_perda_id);
        $this->assertNull($orcamento->proximo_contato_em);
        $this->assertSame('novo', $orcamento->status); // status operacional intacto
        $this->assertDatabaseHas('orcamento_historicos', [
            'orcamento_id' => $orcamento->id, 'tipo' => 'perda', 'mensagem' => "Venda perdida: {$motivo->nome}. Vai esperar o 13º.",
        ]);
    }

    public function test_descartar_direto_da_caixa_de_entrada(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor);

        $this->actingAs($consultor)->post(route('consultor.funil.perder', $orcamento), [
            'motivo_perda_id' => $this->motivo()->id, 'origem' => 'caixa',
        ])->assertSessionHas('success');

        $this->assertTrue($orcamento->fresh()->estaPerdido());
    }

    public function test_nao_perde_orcamento_em_aprovacao_ou_ganho_nem_com_motivo_inativo(): void
    {
        $consultor = $this->consultor();
        $motivo = $this->motivo();

        foreach (['aprovando', 'aprovado'] as $status) {
            $orcamento = $this->orcamento($consultor, ['status' => $status]);
            $this->actingAs($consultor)->post(route('consultor.funil.perder', $orcamento), [
                'motivo_perda_id' => $motivo->id, 'origem' => $this->origemDe($orcamento),
            ])->assertSessionHas('error', 'Só é possível marcar como perdido um orçamento em negociação.');
            $this->assertNull($orcamento->fresh()->perdido_em);
        }

        $motivo->update(['ativo' => false]);
        $orcamento = $this->orcamento($consultor);
        $this->actingAs($consultor)->post(route('consultor.funil.perder', $orcamento), [
            'motivo_perda_id' => $motivo->id, 'origem' => 'caixa',
        ])->assertSessionHasErrors('motivo_perda_id');
    }

    public function test_perdido_nao_vai_para_aprovacao_nem_pela_tela_antiga(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, [
            'funil_etapa_id' => $this->etapaAberta()->id, 'perdido_em' => now(), 'motivo_perda_id' => $this->motivo()->id,
        ]);

        $this->mover($consultor, $orcamento, $this->etapaSistema(FunilEtapa::APROVACAO))
            ->assertSessionHas('error', 'Só orçamentos em negociação podem ser enviados para aprovação.');

        $this->actingAs($consultor)->put(route('consultor.orcamentos.update', $orcamento), ['status' => 'aprovando'])
            ->assertSessionHas('error');

        $this->actingAs($this->admin())->put(route('admin.orcamentos.update', $orcamento), ['status' => 'aprovando'])
            ->assertSessionHasErrors('status');

        $this->assertSame('novo', $orcamento->fresh()->status);
    }

    // ── Reativação ────────────────────────────────────────────────────────

    public function test_reativar_volta_para_a_primeira_etapa_e_conta_tentativa(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, [
            'funil_etapa_id' => $this->etapaAberta(4)->id, 'perdido_em' => now(), 'motivo_perda_id' => $this->motivo()->id,
            'perda_observacao' => 'x',
        ]);

        $this->actingAs($consultor)->post(route('consultor.funil.reativar', $orcamento))->assertSessionHas('success');

        $orcamento->refresh();
        $this->assertNull($orcamento->perdido_em);
        $this->assertNull($orcamento->motivo_perda_id);
        $this->assertNull($orcamento->perda_observacao);
        $this->assertSame($this->etapaAberta(1)->id, $orcamento->funil_etapa_id);
        $this->assertSame(1, $orcamento->tentativas_reativacao);
        $this->assertDatabaseHas('orcamento_historicos', ['orcamento_id' => $orcamento->id, 'tipo' => 'reativacao']);
    }

    public function test_arrastar_de_perdido_para_uma_etapa_reativa(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['perdido_em' => now(), 'motivo_perda_id' => $this->motivo()->id]);

        $this->mover($consultor, $orcamento, $this->etapaAberta(3))->assertSessionMissing('error');

        $orcamento->refresh();
        $this->assertFalse($orcamento->estaPerdido());
        $this->assertSame($this->etapaAberta(3)->id, $orcamento->funil_etapa_id);
        $this->assertSame(1, $orcamento->tentativas_reativacao);
    }

    public function test_reativar_orcamento_que_nao_esta_perdido_e_recusado(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta()->id]);

        $this->actingAs($consultor)->post(route('consultor.funil.reativar', $orcamento))
            ->assertSessionHas('error', 'Este orçamento não está perdido.');
        $this->actingAs($this->consultor())->post(route('consultor.funil.reativar', $orcamento))->assertForbidden();
    }

    // ── Follow-up ─────────────────────────────────────────────────────────

    public function test_registrar_contato_com_nota_e_proxima_data(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta()->id]);
        $data = now()->addDays(2)->setTime(14, 30);

        $this->actingAs($consultor)->post(route('consultor.funil.contato', $orcamento), [
            'nota' => 'Cliente pediu para ligar depois.', 'proximo_contato_em' => $data->toDateTimeString(),
        ])->assertSessionHas('success');

        $this->assertEquals($data, $orcamento->fresh()->proximo_contato_em);
        $this->assertDatabaseHas('orcamento_historicos', [
            'orcamento_id' => $orcamento->id, 'tipo' => 'contato',
            // Gravado em UTC, exibido no fuso de exibição (padrão America/Sao_Paulo, UTC−3).
            'mensagem' => 'Cliente pediu para ligar depois. Próximo contato: '.$data->copy()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i').'.',
        ]);
    }

    public function test_contato_exige_nota_ou_data_e_nao_vale_para_venda_ganha(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor);

        $this->actingAs($consultor)->post(route('consultor.funil.contato', $orcamento), [])
            ->assertSessionHasErrors('nota');

        $ganho = $this->orcamento($consultor, ['status' => 'aprovado']);
        $this->actingAs($consultor)->post(route('consultor.funil.contato', $ganho), ['nota' => 'Oi'])
            ->assertSessionHas('error');
    }
}
