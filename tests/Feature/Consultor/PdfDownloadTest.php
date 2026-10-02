<?php

namespace Tests\Feature\Consultor;

use App\Models\CidadeEstado;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function criarOrcamento(User $consultor): Orcamento
    {
        $cidade = CidadeEstado::create(['cidade' => 'Fortaleza', 'estado' => 'Ceará', 'sigla' => 'CE']);

        $cliente = Cliente::create([
            'consultor_id' => $consultor->id,
            'cidade_id' => $cidade->id,
            'tipo_pessoa' => 'pf',
            'nome' => 'Cliente de Teste',
            'status' => 'novo',
        ]);

        $orcamento = Orcamento::create([
            'consultor_id' => $consultor->id,
            'cliente_id' => $cliente->id,
            'cidade_id' => $cidade->id,
            'status' => 'novo',
            'grupo_tarifario' => 'B1',
            'preco_total' => 25000,
            'geracao_estimada' => 650,
        ]);

        OrcamentoItem::create([
            'orcamento_id' => $orcamento->id,
            'tipo' => 'kit',
            'descricao' => 'Kit 5kWp',
            'quantidade' => 1,
            'preco_custo_unitario' => 18000,
            'preco_venda_unitario' => 25000,
            'preco_venda_total' => 25000,
            'metadados' => ['potencia_kwp' => 5.0],
            'ordem' => 1,
        ]);

        return $orcamento;
    }

    public function test_consultor_dono_do_orcamento_baixa_o_pdf(): void
    {
        $consultor = User::create([
            'name' => 'Consultor Dono', 'email' => 'dono.orc@teste.com',
            'password' => 'senha', 'tipo' => 'consultor', 'status' => true,
        ]);
        $orcamento = $this->criarOrcamento($consultor);

        $response = $this->actingAs($consultor)->get(route('consultor.orcamentos.pdf', $orcamento));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_outro_consultor_nao_acessa_pdf_de_orcamento_alheio(): void
    {
        $dono = User::create(['name' => 'Dono', 'email' => 'dono2.orc@teste.com', 'password' => 'senha', 'tipo' => 'consultor', 'status' => true]);
        $outro = User::create(['name' => 'Outro', 'email' => 'outro.orc@teste.com', 'password' => 'senha', 'tipo' => 'consultor', 'status' => true]);
        $orcamento = $this->criarOrcamento($dono);

        $response = $this->actingAs($outro)->get(route('consultor.orcamentos.pdf', $orcamento));

        $response->assertForbidden();
    }

    public function test_consultor_dono_do_contrato_baixa_o_pdf(): void
    {
        $consultor = User::create([
            'name' => 'Consultor Contrato', 'email' => 'dono.ctr@teste.com',
            'password' => 'senha', 'tipo' => 'consultor', 'status' => true,
        ]);
        $orcamento = $this->criarOrcamento($consultor);

        $contrato = Contrato::create([
            'orcamento_id' => $orcamento->id, 'consultor_id' => $consultor->id,
            'nome_cliente' => 'Cliente de Teste', 'documento_cliente' => '000.000.000-00',
            'endereco_instalacao' => 'Rua Teste, 123', 'potencia_kwp' => 5,
            'qtd_paineis' => 10, 'qtd_inversores' => 1, 'modelo_inversor' => 'Inversor X',
            'consumo_mensal' => 500, 'geracao_estimada' => 650,
            'garantia_paineis' => '25 anos', 'garantia_inversores' => '10 anos',
            'valor_total' => 25000, 'formas_pagamento' => 'À vista',
            'produtos_snapshot' => [], 'status' => 'gerado',
        ]);

        $response = $this->actingAs($consultor)->get(route('consultor.contratos.pdf', $contrato));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_outro_consultor_nao_acessa_contrato_alheio(): void
    {
        $dono = User::create(['name' => 'Dono', 'email' => 'dono3.ctr@teste.com', 'password' => 'senha', 'tipo' => 'consultor', 'status' => true]);
        $outro = User::create(['name' => 'Outro', 'email' => 'outro.ctr@teste.com', 'password' => 'senha', 'tipo' => 'consultor', 'status' => true]);
        $orcamento = $this->criarOrcamento($dono);

        $contrato = Contrato::create([
            'orcamento_id' => $orcamento->id, 'consultor_id' => $dono->id,
            'nome_cliente' => 'Cliente de Teste', 'documento_cliente' => '000.000.000-00',
            'endereco_instalacao' => 'Rua Teste, 123', 'potencia_kwp' => 5,
            'qtd_paineis' => 10, 'qtd_inversores' => 1, 'modelo_inversor' => 'Inversor X',
            'consumo_mensal' => 500, 'geracao_estimada' => 650,
            'garantia_paineis' => '25 anos', 'garantia_inversores' => '10 anos',
            'valor_total' => 25000, 'formas_pagamento' => 'À vista',
            'produtos_snapshot' => [], 'status' => 'gerado',
        ]);

        $this->actingAs($outro)->get(route('consultor.contratos.show', $contrato))->assertForbidden();
        $this->actingAs($outro)->get(route('consultor.contratos.pdf', $contrato))->assertForbidden();
    }
}
