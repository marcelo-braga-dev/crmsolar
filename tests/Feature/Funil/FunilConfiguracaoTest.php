<?php

namespace Tests\Feature\Funil;

use App\Models\Config;
use App\Models\FunilEtapa;
use App\Models\MotivoPerda;
use App\Models\OrcamentoHistorico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CriaDados;
use Tests\Concerns\CriaFunil;
use Tests\TestCase;

/**
 * Configurações → Funil de vendas: etapas, motivos de perda e parâmetros,
 * com as proteções da documentação (seções 13 e 17).
 */
class FunilConfiguracaoTest extends TestCase
{
    use CriaDados, CriaFunil, RefreshDatabase;

    public function test_migration_cria_etapas_e_motivos_padrao(): void
    {
        $this->assertSame(6, FunilEtapa::where('tipo', FunilEtapa::ABERTA)->count());
        foreach (FunilEtapa::TIPOS_SISTEMA as $tipo) {
            $this->assertSame(1, FunilEtapa::where('tipo', $tipo)->count());
        }
        $this->assertSame(7, MotivoPerda::count());
    }

    public function test_migration_coloca_reprovados_na_primeira_etapa(): void
    {
        // Desfaz só a migration do funil (não "a última": outras migrations vieram depois).
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/2026_10_04_000000_create_funil_de_vendas.php']);
        $consultor = $this->consultor();
        $cliente = $this->cliente($consultor);
        $id = DB::table('orcamentos')->insertGetId([
            'consultor_id' => $consultor->id, 'cliente_id' => $cliente->id, 'cidade_id' => $cliente->cidade_id, 'status' => 'aprovacao_reprovada',
            'token' => 'tok', 'preco_total' => 1000, 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3),
        ]);

        Artisan::call('migrate');

        $orcamento = DB::table('orcamentos')->find($id);
        $this->assertSame($this->etapaAberta(1)->id, (int) $orcamento->funil_etapa_id);
        $this->assertSame($orcamento->updated_at, $orcamento->etapa_entrou_em);
    }

    public function test_tela_de_configuracao(): void
    {
        $this->orcamento($this->consultor(), ['funil_etapa_id' => $this->etapaAberta()->id]);

        $this->actingAs($this->admin())->get(route('admin.configuracoes.funil.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Configuracoes/Funil/Index')
                ->has('etapas', 9)
                ->where('etapas.0.orcamentos_count', 1)
                ->has('motivos', 7)
                ->where('parametros.dias_caixa_entrada', 7));

        $this->actingAs($this->consultor())->get(route('admin.configuracoes.funil.index'))->assertForbidden();
        $this->actingAs($this->consultor())->post(route('admin.configuracoes.funil.etapas.store'), ['nome' => 'X', 'cor' => '#000000'])
            ->assertForbidden();
    }

    // ── Etapas ────────────────────────────────────────────────────────────

    public function test_cria_etapa_aberta_no_fim_das_abertas(): void
    {
        $this->actingAs($this->admin())->post(route('admin.configuracoes.funil.etapas.store'), [
            'nome' => 'Pós-proposta', 'cor' => '#ABCDEF', 'probabilidade' => 40, 'sla_dias' => 3,
            'tipo' => FunilEtapa::GANHO, // ignorado: só se cria etapa aberta
        ])->assertSessionHasNoErrors();

        $etapa = FunilEtapa::where('nome', 'Pós-proposta')->sole();
        $this->assertSame(FunilEtapa::ABERTA, $etapa->tipo);
        $this->assertSame(7, $etapa->ordem);
        $this->assertSame(1, FunilEtapa::where('tipo', FunilEtapa::GANHO)->count());
    }

    public function test_etapa_exige_nome_unico_e_cor_hexadecimal(): void
    {
        $this->actingAs($this->admin())->post(route('admin.configuracoes.funil.etapas.store'), [
            'nome' => 'Negociação', 'cor' => 'azul', 'probabilidade' => 150,
        ])->assertSessionHasErrors(['nome', 'cor', 'probabilidade']);
    }

    public function test_etapa_de_sistema_so_muda_nome_e_cor(): void
    {
        $ganho = $this->etapaSistema(FunilEtapa::GANHO);

        $this->actingAs($this->admin())->put(route('admin.configuracoes.funil.etapas.update', $ganho), [
            'nome' => 'Venda fechada', 'cor' => '#00AA00', 'probabilidade' => 10, 'sla_dias' => 9, 'ativa' => false,
        ])->assertSessionHasNoErrors();

        $ganho->refresh();
        $this->assertSame('Venda fechada', $ganho->nome);
        $this->assertSame('#00AA00', $ganho->cor);
        $this->assertSame(100, $ganho->probabilidade);
        $this->assertNull($ganho->sla_dias);
        $this->assertTrue($ganho->ativa);
    }

    public function test_aprovacao_aceita_sla(): void
    {
        $aprovacao = $this->etapaSistema(FunilEtapa::APROVACAO);

        $this->actingAs($this->admin())->put(route('admin.configuracoes.funil.etapas.update', $aprovacao), [
            'nome' => $aprovacao->nome, 'cor' => $aprovacao->cor, 'sla_dias' => 4,
        ]);

        $this->assertSame(4, $aprovacao->fresh()->sla_dias);
    }

    public function test_desativar_etapa_com_negociacoes_e_recusado(): void
    {
        $etapa = $this->etapaAberta(2);
        $this->orcamento($this->consultor(), ['funil_etapa_id' => $etapa->id]);

        $this->actingAs($this->admin())->put(route('admin.configuracoes.funil.etapas.update', $etapa), [
            'nome' => $etapa->nome, 'cor' => $etapa->cor, 'ativa' => false,
        ])->assertSessionHas('error');

        $this->assertTrue($etapa->fresh()->ativa);
    }

    public function test_desativar_etapa_vazia_funciona_mas_nao_a_ultima(): void
    {
        $admin = $this->admin();
        $abertas = FunilEtapa::where('tipo', FunilEtapa::ABERTA)->orderBy('ordem')->get();

        foreach ($abertas->slice(1) as $etapa) {
            $this->actingAs($admin)->put(route('admin.configuracoes.funil.etapas.update', $etapa), [
                'nome' => $etapa->nome, 'cor' => $etapa->cor, 'ativa' => false,
            ])->assertSessionHas('success');
        }

        $ultima = $abertas->first();
        $this->actingAs($admin)->put(route('admin.configuracoes.funil.etapas.update', $ultima), [
            'nome' => $ultima->nome, 'cor' => $ultima->cor, 'ativa' => false,
        ])->assertSessionHas('error', 'O funil precisa de pelo menos uma etapa aberta ativa.');
        $this->assertTrue($ultima->fresh()->ativa);
    }

    public function test_etapa_de_sistema_nao_e_excluida(): void
    {
        foreach (FunilEtapa::TIPOS_SISTEMA as $tipo) {
            $this->actingAs($this->admin())->delete(route('admin.configuracoes.funil.etapas.destroy', $this->etapaSistema($tipo)))
                ->assertSessionHas('error', 'Etapas de sistema não podem ser excluídas.');
        }
        $this->assertSame(9, FunilEtapa::count());
    }

    public function test_excluir_etapa_com_orcamentos_exige_destino_e_move_todos(): void
    {
        $admin = $this->admin();
        $etapa = $this->etapaAberta(3);
        $destino = $this->etapaAberta(4);
        $consultor = $this->consultor();
        $emNegociacao = $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id]);
        $emAprovacao = $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id, 'status' => 'aprovando']);
        $excluido = $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id]);
        $excluido->delete();

        $this->actingAs($admin)->delete(route('admin.configuracoes.funil.etapas.destroy', $etapa))
            ->assertSessionHasErrors('destino_id');
        $this->actingAs($admin)->delete(route('admin.configuracoes.funil.etapas.destroy', $etapa), ['destino_id' => $etapa->id])
            ->assertSessionHasErrors('destino_id');
        $this->assertNotNull($etapa->fresh());

        $this->actingAs($admin)->delete(route('admin.configuracoes.funil.etapas.destroy', $etapa), ['destino_id' => $destino->id])
            ->assertSessionHas('success');

        $this->assertNull($etapa->fresh());
        foreach ([$emNegociacao, $emAprovacao, $excluido] as $orcamento) {
            $this->assertSame($destino->id, $orcamento->fresh()->funil_etapa_id);
        }
        $this->assertSame(3, OrcamentoHistorico::where('tipo', 'etapa')->count());
    }

    public function test_excluir_etapa_vazia_nao_pede_destino(): void
    {
        $etapa = $this->etapaAberta(5);

        $this->actingAs($this->admin())->delete(route('admin.configuracoes.funil.etapas.destroy', $etapa))
            ->assertSessionHas('success');

        $this->assertNull($etapa->fresh());
    }

    public function test_reordenar_etapas(): void
    {
        $ids = FunilEtapa::where('tipo', FunilEtapa::ABERTA)->orderBy('ordem')->pluck('id')->reverse()->values()->all();

        $this->actingAs($this->admin())->put(route('admin.configuracoes.funil.etapas.reordenar'), ['ids' => $ids])
            ->assertSessionHas('success');

        $this->assertSame($ids, FunilEtapa::abertas()->pluck('id')->all());
    }

    public function test_reordenar_com_lista_desatualizada_e_recusado(): void
    {
        $ids = FunilEtapa::where('tipo', FunilEtapa::ABERTA)->orderBy('ordem')->pluck('id')->all();
        $antes = FunilEtapa::abertas()->pluck('id')->all();

        $this->actingAs($this->admin())->put(route('admin.configuracoes.funil.etapas.reordenar'), ['ids' => array_slice($ids, 1)])
            ->assertSessionHas('error');
        $this->actingAs($this->admin())->put(route('admin.configuracoes.funil.etapas.reordenar'), [
            'ids' => [...$ids, $this->etapaSistema(FunilEtapa::GANHO)->id],
        ])->assertSessionHasErrors();

        $this->assertSame($antes, FunilEtapa::abertas()->pluck('id')->all());
    }

    // ── Motivos de perda ──────────────────────────────────────────────────

    public function test_crud_de_motivo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.configuracoes.funil.motivos.store'), [
            'nome' => 'Telhado inadequado', 'reativavel' => false, 'reativar_apos_dias' => 30,
        ])->assertSessionHasNoErrors();
        $motivo = MotivoPerda::where('nome', 'Telhado inadequado')->sole();
        $this->assertNull($motivo->reativar_apos_dias); // sem reativação, prazo não faz sentido

        $this->actingAs($admin)->put(route('admin.configuracoes.funil.motivos.update', $motivo), [
            'nome' => 'Telhado inadequado', 'reativavel' => true, 'reativar_apos_dias' => 45, 'ativo' => false,
        ])->assertSessionHasNoErrors();
        $motivo->refresh();
        $this->assertTrue($motivo->reativavel);
        $this->assertSame(45, $motivo->reativar_apos_dias);
        $this->assertFalse($motivo->ativo);

        $this->actingAs($admin)->delete(route('admin.configuracoes.funil.motivos.destroy', $motivo))->assertSessionHas('success');
        $this->assertNull($motivo->fresh());
    }

    public function test_motivo_reativavel_exige_prazo_e_nome_unico(): void
    {
        $this->actingAs($this->admin())->post(route('admin.configuracoes.funil.motivos.store'), [
            'nome' => 'Preço alto', 'reativavel' => true,
        ])->assertSessionHasErrors(['nome', 'reativar_apos_dias']);
    }

    public function test_motivo_em_uso_nao_e_excluido(): void
    {
        $motivo = $this->motivo();
        $this->orcamento($this->consultor(), ['perdido_em' => now(), 'motivo_perda_id' => $motivo->id]);

        $this->actingAs($this->admin())->delete(route('admin.configuracoes.funil.motivos.destroy', $motivo))
            ->assertSessionHas('error', 'Motivo já usado em orçamentos: desative-o em vez de excluir.');
        $this->assertNotNull($motivo->fresh());
    }

    // ── Parâmetros ────────────────────────────────────────────────────────

    public function test_parametros(): void
    {
        $this->actingAs($this->admin())->put(route('admin.configuracoes.funil.parametros'), [
            'dias_caixa_entrada' => 5, 'max_tentativas_reativacao' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(5, Config::get('funil.dias_caixa_entrada'));
        $this->assertEquals(2, Config::get('funil.max_tentativas_reativacao'));

        $this->actingAs($this->admin())->put(route('admin.configuracoes.funil.parametros'), [
            'dias_caixa_entrada' => 0, 'max_tentativas_reativacao' => 50,
        ])->assertSessionHasErrors(['dias_caixa_entrada', 'max_tentativas_reativacao']);
    }
}
