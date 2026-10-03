<?php

namespace Tests\Feature\Consultor;

use App\Models\CidadeEstado;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\OrcamentoInfo;
use App\Models\OrcamentoItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContratoCreationTest extends TestCase
{
    use RefreshDatabase;

    private function orcamentoAprovado(User $consultor): Orcamento
    {
        $cidade = CidadeEstado::create(['cidade' => 'Fortaleza', 'estado' => 'Ceará', 'sigla' => 'CE']);
        $cliente = Cliente::create([
            'consultor_id' => $consultor->id, 'cidade_id' => $cidade->id,
            'tipo_pessoa' => 'pf', 'nome' => 'Cliente Contrato', 'status' => 'novo',
        ]);
        $orcamento = Orcamento::create([
            'consultor_id' => $consultor->id, 'cliente_id' => $cliente->id, 'cidade_id' => $cidade->id,
            'status' => 'aprovado', 'grupo_tarifario' => 'B1',
            'preco_total' => 25000, 'geracao_estimada' => 650,
        ]);
        OrcamentoItem::create([
            'orcamento_id' => $orcamento->id, 'tipo' => 'kit', 'descricao' => 'Kit 5kWp',
            'quantidade' => 1, 'preco_custo_unitario' => 18000, 'preco_venda_unitario' => 25000,
            'preco_venda_total' => 25000, 'metadados' => ['potencia_kwp' => 5.0], 'ordem' => 1,
        ]);

        return $orcamento;
    }

    private function consultor(): User
    {
        return User::create([
            'name' => 'Consultor Contrato', 'email' => 'consultor.criacontrato@teste.com',
            'password' => 'senha', 'tipo' => 'consultor', 'status' => true,
        ]);
    }

    public function test_tela_de_criacao_vem_pre_preenchida_com_dados_do_orcamento(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamentoAprovado($consultor);

        $this->actingAs($consultor)
            ->get(route('consultor.contratos.create', $orcamento))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Consultor/Contratos/Create')
                ->where('sugestao.nome_cliente', 'Cliente Contrato')
                ->where('sugestao.potencia_kwp', 5)
                ->where('sugestao.valor_total', '25000.00')
            );
    }

    public function test_nao_permite_gerar_contrato_para_orcamento_nao_aprovado(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamentoAprovado($consultor);
        $orcamento->update(['status' => 'novo']);

        $this->actingAs($consultor)
            ->get(route('consultor.contratos.create', $orcamento))
            ->assertForbidden();
    }

    public function test_gera_contrato_com_snapshot_dos_itens_do_orcamento(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamentoAprovado($consultor);

        $response = $this->actingAs($consultor)->post(route('consultor.contratos.store'), [
            'orcamento_id' => $orcamento->id,
            'nome_cliente' => 'Cliente Contrato',
            'documento_cliente' => '000.000.000-00',
            'endereco_instalacao' => 'Rua Teste, 123',
            'potencia_kwp' => 5,
            'qtd_paineis' => 10,
            'qtd_inversores' => 1,
            'modelo_inversor' => 'Inversor X 5kW',
            'consumo_mensal' => 500,
            'geracao_estimada' => 650,
            'garantia_paineis' => '25 anos',
            'garantia_inversores' => '10 anos',
            'valor_total' => 25000,
            'formas_pagamento' => 'À vista',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contratos', [
            'orcamento_id' => $orcamento->id, 'consultor_id' => $consultor->id, 'status' => 'gerado',
        ]);

        $contrato = $orcamento->fresh()->contrato;
        $this->assertNotNull($contrato);
        $this->assertNotEmpty($contrato->produtos_snapshot);
        $this->assertSame('Kit 5kWp', $contrato->produtos_snapshot[0]['descricao']);
    }

    public function test_acessar_criacao_de_contrato_ja_existente_redireciona_para_ele(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamentoAprovado($consultor);

        $this->actingAs($consultor)->post(route('consultor.contratos.store'), [
            'orcamento_id' => $orcamento->id,
            'nome_cliente' => 'Cliente Contrato',
            'documento_cliente' => '000.000.000-00',
            'endereco_instalacao' => 'Rua Teste, 123',
            'potencia_kwp' => 5,
            'qtd_paineis' => 10,
            'qtd_inversores' => 1,
            'modelo_inversor' => 'Inversor X 5kW',
            'consumo_mensal' => 500,
            'geracao_estimada' => 650,
            'garantia_paineis' => '25 anos',
            'garantia_inversores' => '10 anos',
            'valor_total' => 25000,
            'formas_pagamento' => 'À vista',
        ]);

        $contrato = $orcamento->fresh()->contrato;

        $this->actingAs($consultor)
            ->get(route('consultor.contratos.create', $orcamento))
            ->assertRedirect(route('consultor.contratos.show', $contrato));
    }

    private function dadosContrato(Orcamento $orcamento, array $extra = []): array
    {
        return $extra + [
            'orcamento_id' => $orcamento->id,
            'nome_cliente' => 'Cliente Contrato',
            'documento_cliente' => '000.000.000-00',
            'endereco_instalacao' => 'Rua Teste, 123',
            'potencia_kwp' => 5,
            'qtd_paineis' => 10,
            'qtd_inversores' => 1,
            'modelo_inversor' => 'Inversor X 5kW',
            'consumo_mensal' => 500,
            'geracao_estimada' => 650,
            'garantia_paineis' => '25 anos',
            'garantia_inversores' => '10 anos',
            'formas_pagamento' => 'À vista',
        ];
    }

    public function test_valor_do_contrato_vem_do_orcamento_e_nao_do_formulario(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamentoAprovado($consultor);

        $this->actingAs($consultor)
            ->post(route('consultor.contratos.store'), $this->dadosContrato($orcamento, ['valor_total' => 1]))
            ->assertRedirect();

        $this->assertEquals(25000.00, (float) $orcamento->fresh()->contrato->valor_total);
    }

    public function test_store_rejeita_orcamento_nao_aprovado(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamentoAprovado($consultor);
        $orcamento->update(['status' => 'novo']);

        $this->actingAs($consultor)
            ->post(route('consultor.contratos.store'), $this->dadosContrato($orcamento))
            ->assertForbidden();

        $this->assertDatabaseCount('contratos', 0);
    }

    public function test_store_nao_duplica_contrato_do_mesmo_orcamento(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamentoAprovado($consultor);

        $this->actingAs($consultor)->post(route('consultor.contratos.store'), $this->dadosContrato($orcamento));
        $this->actingAs($consultor)
            ->post(route('consultor.contratos.store'), $this->dadosContrato($orcamento))
            ->assertRedirect(route('consultor.contratos.show', $orcamento->fresh()->contrato));

        $this->assertDatabaseCount('contratos', 1);
    }

    public function test_dados_tecnicos_do_contrato_vem_do_orcamento_e_nao_do_formulario(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamentoAprovado($consultor);
        OrcamentoInfo::create(['orcamento_id' => $orcamento->id, 'consumo' => 480.6, 'tensao' => 220, 'orientacao' => 'norte']);

        $this->actingAs($consultor)
            ->post(route('consultor.contratos.store'), $this->dadosContrato($orcamento, [
                'potencia_kwp' => 99, 'geracao_estimada' => 99999, 'consumo_mensal' => 1,
            ]))
            ->assertRedirect();

        $contrato = $orcamento->fresh()->contrato;
        $this->assertEquals(5.0, (float) $contrato->potencia_kwp);
        $this->assertSame(650, $contrato->geracao_estimada);
        $this->assertSame(481, $contrato->consumo_mensal);
    }

    public function test_dado_tecnico_ausente_no_orcamento_vem_do_formulario(): void
    {
        $consultor = $this->consultor();
        $orcamento = $this->orcamentoAprovado($consultor); // sem OrcamentoInfo → sem consumo

        $this->actingAs($consultor)
            ->post(route('consultor.contratos.store'), $this->dadosContrato($orcamento, ['consumo_mensal' => 520]))
            ->assertRedirect();

        $this->assertSame(520, $orcamento->fresh()->contrato->consumo_mensal);
    }
}
