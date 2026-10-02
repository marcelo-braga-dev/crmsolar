<?php

namespace Tests\Feature\Api;

use App\Models\CidadeEstado;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\OrcamentoInfo;
use App\Models\OrcamentoItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrcamentoPublicoTest extends TestCase
{
    use RefreshDatabase;

    private function orcamento(): Orcamento
    {
        $consultor = User::create([
            'name' => 'Consultor Público', 'email' => 'consultor.publico@teste.com',
            'password' => 'senha', 'tipo' => 'consultor', 'status' => true,
            'comissao_percentual' => 5, 'celular' => '(85) 99999-0000',
        ]);
        $cidade = CidadeEstado::create(['cidade' => 'Fortaleza', 'estado' => 'Ceará', 'sigla' => 'CE']);
        $cliente = Cliente::create([
            'consultor_id' => $consultor->id, 'cidade_id' => $cidade->id, 'tipo_pessoa' => 'pf',
            'nome' => 'Cliente Público', 'cpf' => '123.456.789-00', 'rg' => '1234567', 'status' => 'novo',
        ]);
        $orcamento = Orcamento::create([
            'consultor_id' => $consultor->id, 'cliente_id' => $cliente->id, 'cidade_id' => $cidade->id,
            'status' => 'novo', 'grupo_tarifario' => 'B1', 'preco_total' => 25000,
            'geracao_estimada' => 650, 'anotacoes' => 'Anotação interna',
        ]);
        OrcamentoInfo::create([
            'orcamento_id' => $orcamento->id, 'consumo' => 500, 'tensao' => 220,
            'anotacoes_tecnicas' => 'Nota técnica interna', 'analise_economica' => ['payback_simples' => 4.2],
        ]);
        OrcamentoItem::create([
            'orcamento_id' => $orcamento->id, 'tipo' => 'kit', 'descricao' => 'Kit 5kWp',
            'quantidade' => 1, 'preco_custo_unitario' => 18000, 'preco_venda_unitario' => 25000,
            'preco_venda_total' => 25000, 'margem_percentual' => 38.889, 'comissao_percentual' => 5,
            'metadados' => ['potencia_kwp' => 5.0], 'ordem' => 1,
        ]);

        return $orcamento;
    }

    public function test_proposta_publica_retorna_dados_da_proposta(): void
    {
        $orcamento = $this->orcamento();

        $this->getJson(route('api.orcamento.public', $orcamento->token))
            ->assertOk()
            ->assertJsonPath('id', $orcamento->id)
            ->assertJsonPath('cliente.nome', 'Cliente Público')
            ->assertJsonPath('consultor.nome', 'Consultor Público')
            ->assertJsonPath('itens.0.descricao', 'Kit 5kWp')
            ->assertJsonPath('itens.0.preco_venda_total', 25000)
            ->assertJsonPath('info.analise_economica.payback_simples', 4.2);
    }

    public function test_proposta_publica_nao_expoe_documentos_custo_margem_nem_anotacoes(): void
    {
        $orcamento = $this->orcamento();

        $json = $this->getJson(route('api.orcamento.public', $orcamento->token))->assertOk()->getContent();

        foreach (['123.456.789-00', '1234567', 'preco_custo', 'margem_percentual', 'comissao_percentual',
            'Anotação interna', 'Nota técnica interna', 'token', 'password'] as $proibido) {
            $this->assertStringNotContainsString($proibido, $json, "Resposta pública contém '{$proibido}'");
        }
    }

    public function test_token_inexistente_retorna_404(): void
    {
        $this->getJson(route('api.orcamento.public', 'nao-existe'))->assertNotFound();
    }
}
