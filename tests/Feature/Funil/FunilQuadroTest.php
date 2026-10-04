<?php

namespace Tests\Feature\Funil;

use App\Models\Config;
use App\Models\FunilEtapa;
use App\Models\Orcamento;
use App\Services\Funil\FunilService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CriaDados;
use Tests\Concerns\CriaFunil;
use Tests\TestCase;

/**
 * Montagem do quadro: em que coluna cada orçamento aparece, escopo por usuário, filtros e totais.
 * Regra de posicionamento: docs/funil-de-vendas.md, seção 6.
 */
class FunilQuadroTest extends TestCase
{
    use CriaDados, CriaFunil, RefreshDatabase;

    /** @return array<string, array{string, bool, bool, string}> [status, com etapa 2?, perdido?, coluna esperada] */
    public static function posicionamentos(): array
    {
        return [
            'novo sem etapa → caixa' => ['novo', false, false, 'caixa'],
            'novo com etapa → a etapa' => ['novo', true, false, 'etapa2'],
            'reprovado sem etapa → primeira etapa' => ['aprovacao_reprovada', false, false, 'etapa1'],
            'reprovado com etapa → a etapa' => ['aprovacao_reprovada', true, false, 'etapa2'],
            'aprovando → em aprovação' => ['aprovando', true, false, FunilEtapa::APROVACAO],
            'aprovado → ganho' => ['aprovado', true, false, FunilEtapa::GANHO],
            'instalando → ganho' => ['instalando', false, false, FunilEtapa::GANHO],
            'finalizado → ganho' => ['finalizado', false, false, FunilEtapa::GANHO],
            'perdido com etapa → perdido' => ['novo', true, true, FunilEtapa::PERDIDO],
            'perdido na caixa → perdido' => ['novo', false, true, FunilEtapa::PERDIDO],
        ];
    }

    #[DataProvider('posicionamentos')]
    public function test_posicionamento_do_card(string $status, bool $comEtapa, bool $perdido, string $esperado): void
    {
        $orcamento = $this->orcamento($this->consultor(), [
            'status' => $status,
            'funil_etapa_id' => $comEtapa ? $this->etapaAberta(2)->id : null,
            'perdido_em' => $perdido ? now() : null,
            'motivo_perda_id' => $perdido ? $this->motivo()->id : null,
        ]);

        $etapa = app(FunilService::class)->etapaDe($orcamento);

        $esperadoId = match ($esperado) {
            'caixa' => null,
            'etapa1' => $this->etapaAberta(1)->id,
            'etapa2' => $this->etapaAberta(2)->id,
            default => $this->etapaSistema($esperado)->id,
        };
        $this->assertSame($esperadoId, $etapa?->id);
    }

    public function test_card_de_etapa_desativada_cai_na_primeira_etapa(): void
    {
        $etapa = $this->etapaAberta(3);
        $orcamento = $this->orcamento($this->consultor(), ['funil_etapa_id' => $etapa->id]);
        $etapa->update(['ativa' => false]);

        $this->assertSame($this->etapaAberta(1)->id, app(FunilService::class)->etapaDe($orcamento)->id);
    }

    public function test_quadro_entrega_colunas_na_ordem_com_cards_e_caixa_de_entrada(): void
    {
        $consultor = $this->consultor();
        $naCaixa = $this->orcamento($consultor);
        $emNegociacao = $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta(2)->id]);
        $this->orcamento($consultor, ['status' => 'aprovando']);

        $this->actingAs($consultor)->get(route('consultor.funil.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Funil/Index')
                ->where('area', 'consultor')
                ->has('colunas', 9) // 6 abertas + aprovação + ganho + perdido
                ->where('colunas.0.nome', 'Primeiro contato')
                ->where('colunas.1.cards.0.id', $emNegociacao->id)
                ->where('colunas.6.tipo', FunilEtapa::APROVACAO)
                ->where('colunas.6.quantidade', 1)
                ->where('colunas.8.tipo', FunilEtapa::PERDIDO)
                ->has('caixa', 1)
                ->where('caixa.0.id', $naCaixa->id)
                ->where('resumo.caixa', 1)
                ->where('resumo.abertos', 2)
                ->has('motivos', 7));
    }

    public function test_consultor_ve_so_os_seus_e_admin_ve_todos_com_filtro(): void
    {
        $eu = $this->consultor();
        $outro = $this->consultor();
        $meu = $this->orcamento($eu, ['funil_etapa_id' => $this->etapaAberta()->id]);
        $dele = $this->orcamento($outro, ['funil_etapa_id' => $this->etapaAberta()->id]);

        $this->actingAs($eu)->get(route('consultor.funil.index'))
            ->assertInertia(fn ($page) => $page->has('colunas.0.cards', 1)->where('colunas.0.cards.0.id', $meu->id)->where('consultores', []));

        $this->actingAs($this->admin())->get(route('admin.funil.index'))
            ->assertInertia(fn ($page) => $page->where('area', 'admin')->has('colunas.0.cards', 2)->has('consultores', 2));

        $this->actingAs($this->admin())->get(route('admin.funil.index', ['consultor_id' => $outro->id]))
            ->assertInertia(fn ($page) => $page->has('colunas.0.cards', 1)->where('colunas.0.cards.0.id', $dele->id));
    }

    public function test_ganhos_e_perdidos_antigos_saem_do_quadro(): void
    {
        $consultor = $this->consultor();
        $ganhoRecente = $this->orcamento($consultor, ['status' => 'aprovado']);
        $ganhoAntigo = $this->orcamento($consultor, ['status' => 'aprovado']);
        $ganhoAntigo->forceFill(['etapa_entrou_em' => now()->subDays(40)])->saveQuietly();
        $this->orcamento($consultor, ['perdido_em' => now()->subDays(40), 'motivo_perda_id' => $this->motivo()->id]);

        $this->actingAs($consultor)->get(route('consultor.funil.index'))
            ->assertInertia(fn ($page) => $page
                ->where('colunas.7.quantidade', 1)
                ->where('colunas.7.cards.0.id', $ganhoRecente->id)
                ->where('colunas.8.quantidade', 0));
    }

    public function test_totais_e_valor_ponderado_pela_probabilidade(): void
    {
        $consultor = $this->consultor();
        $etapa = $this->etapaAberta(2); // 30%
        $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id, 'preco_total' => 10000]);
        $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id, 'preco_total' => 20000]);

        $this->actingAs($consultor)->get(route('consultor.funil.index'))
            ->assertInertia(fn ($page) => $page
                ->where('colunas.1.quantidade', 2)
                ->where('colunas.1.valor', 30000)
                ->where('colunas.1.ponderado', 9000)
                ->where('resumo.valor_aberto', 30000)
                ->where('resumo.ponderado', 9000));
    }

    public function test_card_mostra_atraso_sla_reprovado_e_contato_vencido(): void
    {
        $consultor = $this->consultor();
        $etapa = $this->etapaAberta(1); // SLA 2 dias
        $orcamento = $this->orcamento($consultor, [
            'status' => 'aprovacao_reprovada', 'funil_etapa_id' => $etapa->id,
            'proximo_contato_em' => now()->subDay(),
        ]);
        $orcamento->forceFill(['etapa_entrou_em' => now()->subDays(5)])->saveQuietly();

        $this->actingAs($consultor)->get(route('consultor.funil.index'))
            ->assertInertia(fn ($page) => $page
                ->where('colunas.0.cards.0.dias_na_etapa', 5)
                ->where('colunas.0.cards.0.sla_estourado', true)
                ->where('colunas.0.cards.0.reprovado', true)
                ->where('colunas.0.cards.0.contato_atrasado', true)
                ->where('resumo.atrasados', 1));
    }

    public function test_contato_atrasado_vem_primeiro_na_coluna(): void
    {
        $consultor = $this->consultor();
        $etapa = $this->etapaAberta();
        $semContato = $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id]);
        $atrasado = $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id, 'proximo_contato_em' => now()->subHour()]);

        $this->actingAs($consultor)->get(route('consultor.funil.index'))
            ->assertInertia(fn ($page) => $page
                ->where('colunas.0.cards.0.id', $atrasado->id)
                ->where('colunas.0.cards.1.id', $semContato->id));
    }

    public function test_caixa_de_entrada_marca_esfriando_conforme_parametro(): void
    {
        Config::set('funil.dias_caixa_entrada', 3, 'funil');
        $consultor = $this->consultor();
        $velho = $this->orcamento($consultor);
        $velho->forceFill(['created_at' => now()->subDays(4)])->saveQuietly();
        $novo = $this->orcamento($consultor);

        $this->actingAs($consultor)->get(route('consultor.funil.index'))
            ->assertInertia(fn ($page) => $page
                ->where('caixa.0.id', $velho->id) // mais antigo primeiro
                ->where('caixa.0.esfriando', true)
                ->where('caixa.1.id', $novo->id)
                ->where('caixa.1.esfriando', false)
                ->where('resumo.dias_caixa_entrada', 3));
    }

    public function test_filtros_de_busca_grupo_e_atrasados(): void
    {
        $consultor = $this->consultor();
        $etapa = $this->etapaAberta();
        $maria = $this->orcamento($consultor, [
            'funil_etapa_id' => $etapa->id, 'grupo_tarifario' => 'B3',
            'cliente_id' => $this->cliente($consultor, ['nome' => 'Maria Souza'])->id,
            'proximo_contato_em' => now()->subDay(),
        ]);
        $this->orcamento($consultor, ['funil_etapa_id' => $etapa->id, 'grupo_tarifario' => 'B1']);

        foreach ([['busca' => 'Maria'], ['busca' => "#{$maria->id}"], ['grupo' => 'B3'], ['atrasados' => 1]] as $filtro) {
            $this->actingAs($consultor)->get(route('consultor.funil.index', $filtro))
                ->assertInertia(fn ($page) => $page->has('colunas.0.cards', 1)->where('colunas.0.cards.0.id', $maria->id));
        }
    }

    public function test_orcamento_excluido_nao_aparece(): void
    {
        $consultor = $this->consultor();
        $this->orcamento($consultor, ['funil_etapa_id' => $this->etapaAberta()->id])->delete();

        $this->actingAs($consultor)->get(route('consultor.funil.index'))
            ->assertInertia(fn ($page) => $page->has('colunas.0.cards', 0));
    }

    public function test_relogio_da_etapa_reinicia_quando_status_muda_de_coluna(): void
    {
        $orcamento = $this->orcamento($this->consultor(), ['status' => 'aprovando']);
        $orcamento->forceFill(['etapa_entrou_em' => now()->subDays(10)])->saveQuietly();

        // Mudança de status que mantém a coluna (instalando → finalizado, ambos Ganho) não reinicia.
        $orcamento->update(['status' => 'aprovado']);
        $this->assertTrue($orcamento->fresh()->etapa_entrou_em->isToday());

        $orcamento->forceFill(['etapa_entrou_em' => now()->subDays(10)])->saveQuietly();
        $orcamento->update(['status' => 'instalando']);
        $this->assertFalse($orcamento->fresh()->etapa_entrou_em->isToday());
    }

    public function test_orcamento_criado_ja_tem_relogio_de_etapa(): void
    {
        $this->assertNotNull(Orcamento::find($this->orcamento($this->consultor())->id)->etapa_entrou_em);
    }
}
