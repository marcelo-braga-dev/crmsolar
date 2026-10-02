<?php

namespace Tests\Feature\Admin;

use App\Models\Orcamento;
use App\Models\OrcamentoHistorico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Máquina de estados de orcamentos.status (Orcamento::TRANSICOES) aplicada pelo Admin.
 */
class TransicoesStatusTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function permitidas(): array
    {
        $casos = [];
        foreach (Orcamento::TRANSICOES as $de => $paras) {
            foreach ($paras as $para) {
                $casos["{$de} → {$para}"] = [$de, $para];
            }
        }

        return $casos;
    }

    /** @return array<string, array{string, string}> */
    public static function bloqueadas(): array
    {
        return [
            'novo → aprovado (pula aprovação)' => ['novo', 'aprovado'],
            'novo → finalizado' => ['novo', 'finalizado'],
            'aprovando → instalando' => ['aprovando', 'instalando'],
            'aprovacao_reprovada → aprovado' => ['aprovacao_reprovada', 'aprovado'],
            'aprovado → finalizado (pula instalação)' => ['aprovado', 'finalizado'],
            'finalizado → instalando' => ['finalizado', 'instalando'],
            'finalizado → novo' => ['finalizado', 'novo'],
        ];
    }

    #[DataProvider('permitidas')]
    public function test_transicao_permitida_altera_e_registra_historico(string $de, string $para): void
    {
        $orcamento = $this->orcamento($this->consultor(), ['status' => $de]);

        $this->actingAs($this->admin())
            ->put(route('admin.orcamentos.update', $orcamento), ['status' => $para])
            ->assertSessionHasNoErrors();

        $this->assertSame($para, $orcamento->fresh()->status);
        $this->assertSame(1, OrcamentoHistorico::where('status', $para)->count());
    }

    #[DataProvider('bloqueadas')]
    public function test_transicao_nao_permitida_e_recusada(string $de, string $para): void
    {
        $orcamento = $this->orcamento($this->consultor(), ['status' => $de]);

        $this->actingAs($this->admin())
            ->put(route('admin.orcamentos.update', $orcamento), ['status' => $para])
            ->assertSessionHasErrors('status');

        $this->assertSame($de, $orcamento->fresh()->status);
        $this->assertSame(0, OrcamentoHistorico::count());
    }

    public function test_manter_o_status_e_salvar_anotacoes_e_sempre_permitido(): void
    {
        $orcamento = $this->orcamento($this->consultor(), ['status' => 'finalizado']);

        $this->actingAs($this->admin())
            ->put(route('admin.orcamentos.update', $orcamento), ['status' => 'finalizado', 'anotacoes' => 'Obra entregue'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Obra entregue', $orcamento->fresh()->anotacoes);
    }

    public function test_tela_recebe_apenas_os_destinos_permitidos(): void
    {
        $orcamento = $this->orcamento($this->consultor(), ['status' => 'aprovado']);

        $this->actingAs($this->admin())->get(route('admin.orcamentos.show', $orcamento))
            ->assertInertia(fn ($page) => $page->where('transicoes', ['instalando', 'aprovando']));
    }

    public function test_consultor_nao_desfaz_aprovacao_mesmo_com_transicao_existente_para_o_admin(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor, ['status' => 'aprovado']);

        $this->actingAs($consultor)
            ->put(route('consultor.orcamentos.update', $orcamento), ['status' => 'aprovando'])
            ->assertSessionHas('error');

        $this->assertSame('aprovado', $orcamento->fresh()->status);
    }
}
