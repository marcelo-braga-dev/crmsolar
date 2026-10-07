<?php

namespace Tests\Feature\Admin;

use App\Models\OrcamentoInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Detalhe do orçamento no Admin. A página lia `item.valor_unitario`/`valor_total` e `info.estrutura`
 * como texto, campos que o servidor nunca enviou: a tela quebrava (página em branco) em todo orçamento.
 */
class OrcamentoDetalheTest extends TestCase
{
    use CriaDados, RefreshDatabase;

    public function test_detalhe_envia_os_campos_que_a_tela_exibe(): void
    {
        $orcamento = $this->orcamento($this->consultor(), ['preco_total' => 38113.37]);
        $this->itemKit($orcamento, ['preco_venda_unitario' => 38113.37, 'preco_venda_total' => 38113.37]);
        OrcamentoInfo::create([
            'orcamento_id' => $orcamento->id, 'estrutura_id' => $this->estrutura(['nome' => 'Telha Colonial'])->id,
            'tipo_dimensionamento' => 'convencional', 'fases' => 'bifasico', 'tensao' => 220,
            'consumo' => 1290, 'tarifa_kwh' => 0.95,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orcamentos.show', $orcamento))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Orcamentos/Show')
                ->where('orcamento.preco_total', '38113.37')
                ->where('orcamento.itens.0.preco_venda_unitario', '38113.37')
                ->where('orcamento.itens.0.preco_venda_total', '38113.37')
                ->missing('orcamento.itens.0.valor_unitario')
                ->where('orcamento.info.estrutura.nome', 'Telha Colonial')
                ->where('orcamento.info.tipo_dimensionamento', 'convencional')
                ->where('orcamento.info.fases', 'bifasico')
                ->where('orcamento.info.tarifa_kwh', '0.95000')
                ->where('orcamento.info.consumo', '1290.00'));
    }

    public function test_detalhe_sem_dados_tecnicos_nem_itens(): void
    {
        $orcamento = $this->orcamento($this->consultor());

        $this->actingAs($this->admin())
            ->get(route('admin.orcamentos.show', $orcamento))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('orcamento.info', null)
                ->where('orcamento.itens', []));
    }

    public function test_consultor_nao_acessa_o_detalhe_do_admin(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamento($consultor);

        $this->actingAs($consultor)
            ->get(route('admin.orcamentos.show', $orcamento))
            ->assertForbidden();
    }
}
