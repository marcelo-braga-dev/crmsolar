<?php

namespace Tests\Feature\Consultor;

use App\Models\CidadeEstado;
use App\Models\Cliente;
use App\Models\Estrutura;
use App\Models\Fornecedor;
use App\Models\Kit;
use App\Models\MargemPrincipal;
use App\Models\Orcamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Geração e preço usados na análise econômica gravada vêm do servidor,
 * não de geracao_estimada/preco_venda enviados pelo navegador.
 */
class AnaliseEconomicaServidorTest extends TestCase
{
    use RefreshDatabase;

    private User $consultor;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consultor = User::create([
            'name' => 'Consultor Análise', 'email' => 'consultor.analise@teste.com',
            'password' => 'senha', 'tipo' => 'consultor', 'status' => true, 'comissao_percentual' => 5,
        ]);
        $cidade = CidadeEstado::create(['cidade' => 'Fortaleza', 'estado' => 'Ceará', 'sigla' => 'CE']);
        $cliente = Cliente::create([
            'consultor_id' => $this->consultor->id, 'cidade_id' => $cidade->id,
            'tipo_pessoa' => 'pf', 'nome' => 'Cliente Análise', 'status' => 'novo',
        ]);
        $fornecedor = Fornecedor::create(['nome' => 'Fornecedor', 'ativo' => true]);
        $estrutura = Estrutura::create(['nome' => 'Telhado', 'ativo' => true]);
        $kit = Kit::create([
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id,
            'nome' => 'Kit 5kWp', 'potencia_kwp' => 5, 'tensao' => 220,
            'preco_custo' => 20000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);
        MargemPrincipal::create(['nome' => 'Padrão', 'potencia_min' => 0, 'potencia_max' => null, 'margem' => 20, 'ordem' => 1]);

        $this->payload = [
            'cliente_id' => $cliente->id, 'estrutura_id' => $estrutura->id, 'tensao' => 220,
            'qtd_kits' => 1, 'orientacao' => 'norte', 'fases' => 'bifasico',
            'consumo' => 600, 'tarifa_kwh' => 0.95, 'objetivo_percentual' => 100,
            'kit_id' => $kit->id,
        ];
    }

    public function test_valores_enviados_pelo_navegador_sao_ignorados(): void
    {
        $this->actingAs($this->consultor)->post(route('consultor.grupo.b1.store'), $this->payload)->assertRedirect();
        $this->actingAs($this->consultor)->post(route('consultor.grupo.b1.store'), $this->payload + [
            'geracao_estimada' => 999999, 'preco_venda' => 1,
        ])->assertRedirect();

        [$honesto, $adulterado] = Orcamento::with('info')->orderBy('id')->get()->all();

        $this->assertEquals(24000.00, (float) $honesto->preco_total); // 20000 × 1,20
        $this->assertSame($honesto->info->analise_economica, $adulterado->info->analise_economica);
        $this->assertGreaterThan(0, $honesto->info->analise_economica['payback_simples']);
    }
}
