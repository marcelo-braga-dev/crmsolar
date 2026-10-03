<?php

namespace Tests\Feature\Services;

use App\Models\Estrutura;
use App\Models\Fornecedor;
use App\Models\Kit;
use App\Models\MargemEstado;
use App\Models\MargemFornecedor;
use App\Models\MargemPrincipal;
use App\Services\PrecificacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrecificacaoServiceTest extends TestCase
{
    use RefreshDatabase;

    private PrecificacaoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PrecificacaoService;
    }

    public function test_calcula_preco_de_venda_somando_as_3_camadas_de_margem(): void
    {
        $fornecedor = Fornecedor::create(['nome' => 'Fornecedor A', 'ativo' => true]);
        $estrutura = Estrutura::create(['nome' => 'Solo', 'ativo' => true]);
        $kit = Kit::create([
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id,
            'nome' => 'Kit 10kWp', 'potencia_kwp' => 10, 'tensao' => 220,
            'preco_custo' => 20000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);

        MargemPrincipal::create(['nome' => 'Padrão', 'potencia_min' => 0, 'potencia_max' => null, 'margem' => 20, 'ordem' => 1]);
        MargemEstado::create(['estado' => 'SP', 'nome_estado' => 'São Paulo', 'margem' => 2]);
        MargemFornecedor::create(['fornecedor_id' => $fornecedor->id, 'margem' => 1]);

        $preco = $this->service->calcular($kit, qtdKits: 1, estado: 'SP');

        // margem total = 20 (principal) + 2 (estado) + 1 (fornecedor) = 23%
        $this->assertEquals(23.0, $preco['margem_total']);
        $this->assertEquals(20000.0, $preco['preco_custo']);
        $this->assertEquals(24600.0, $preco['preco_venda']); // 20000 * 1.23
    }

    public function test_multiplica_preco_de_custo_pela_quantidade_de_kits(): void
    {
        $fornecedor = Fornecedor::create(['nome' => 'Fornecedor B', 'ativo' => true]);
        $estrutura = Estrutura::create(['nome' => 'Telhado', 'ativo' => true]);
        $kit = Kit::create([
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id,
            'nome' => 'Kit 3kWp', 'potencia_kwp' => 3, 'tensao' => 127,
            'preco_custo' => 9000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);

        $preco = $this->service->calcular($kit, qtdKits: 3, estado: 'RJ');

        // Sem margens cadastradas (estado/fornecedor/principal) → margem 0
        $this->assertEquals(0.0, $preco['margem_total']);
        $this->assertEquals(27000.0, $preco['preco_custo']); // 9000 * 3
        $this->assertEquals(27000.0, $preco['preco_venda']);
    }

    public function test_margem_principal_respeita_a_faixa_de_potencia_total(): void
    {
        $fornecedor = Fornecedor::create(['nome' => 'Fornecedor C', 'ativo' => true]);
        $estrutura = Estrutura::create(['nome' => 'Solo', 'ativo' => true]);
        $kit = Kit::create([
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id,
            'nome' => 'Kit 15kWp', 'potencia_kwp' => 15, 'tensao' => 380,
            'preco_custo' => 30000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);

        MargemPrincipal::create(['nome' => 'Até 10kWp', 'potencia_min' => 0, 'potencia_max' => 10, 'margem' => 25, 'ordem' => 1]);
        MargemPrincipal::create(['nome' => 'Acima de 10kWp', 'potencia_min' => 10, 'potencia_max' => null, 'margem' => 15, 'ordem' => 2]);

        // 15 kWp cai na segunda faixa (> 10kWp)
        $preco = $this->service->calcular($kit, qtdKits: 1, estado: 'MG');

        $this->assertEquals(15.0, $preco['margem_total']);
    }

    private function kitSimples(Fornecedor $fornecedor, float $kwp = 5): Kit
    {
        return Kit::create([
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => Estrutura::firstOrCreate(['nome' => 'Solo'], ['ativo' => true])->id,
            'nome' => "Kit {$kwp}kWp", 'potencia_kwp' => $kwp, 'tensao' => 220,
            'preco_custo' => 10000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);
    }

    public function test_potencia_acima_de_todas_as_faixas_usa_a_faixa_mais_alta(): void
    {
        MargemPrincipal::create(['nome' => 'Até 10', 'potencia_min' => 0, 'potencia_max' => 10, 'margem' => 30, 'ordem' => 1]);
        MargemPrincipal::create(['nome' => '10 a 50', 'potencia_min' => 10, 'potencia_max' => 50, 'margem' => 18, 'ordem' => 2]);
        $kit = $this->kitSimples(Fornecedor::create(['nome' => 'F', 'ativo' => true]), 80);

        $this->assertEquals(18.0, $this->service->calcular($kit, 1, 'SP')['margem_principal']);
    }

    public function test_potencia_na_lacuna_entre_faixas_usa_a_faixa_anterior_e_nao_a_mais_alta(): void
    {
        MargemPrincipal::create(['nome' => '0 a 10', 'potencia_min' => 0, 'potencia_max' => 10, 'margem' => 30, 'ordem' => 1]);
        MargemPrincipal::create(['nome' => '10 a 20', 'potencia_min' => 10, 'potencia_max' => 20, 'margem' => 25, 'ordem' => 2]);
        MargemPrincipal::create(['nome' => '20,5 a 50', 'potencia_min' => 20.5, 'potencia_max' => 50, 'margem' => 22, 'ordem' => 3]);
        MargemPrincipal::create(['nome' => '50+', 'potencia_min' => 50, 'potencia_max' => null, 'margem' => 15, 'ordem' => 4]);
        $kit = $this->kitSimples(Fornecedor::create(['nome' => 'F', 'ativo' => true]), 20.2);

        $this->assertEquals(25.0, $this->service->calcular($kit, 1, 'SP')['margem_principal']);
    }

    public function test_potencia_abaixo_da_primeira_faixa_usa_a_de_menor_potencia(): void
    {
        MargemPrincipal::create(['nome' => '1 a 10', 'potencia_min' => 1, 'potencia_max' => 10, 'margem' => 30, 'ordem' => 1]);
        MargemPrincipal::create(['nome' => '10+', 'potencia_min' => 10, 'potencia_max' => null, 'margem' => 15, 'ordem' => 2]);
        $kit = $this->kitSimples(Fornecedor::create(['nome' => 'F', 'ativo' => true]), 0.5);

        $this->assertEquals(30.0, $this->service->calcular($kit, 1, 'SP')['margem_principal']);
    }

    public function test_sem_nenhuma_margem_cadastrada_vende_pelo_custo(): void
    {
        $kit = $this->kitSimples(Fornecedor::create(['nome' => 'F', 'ativo' => true]));

        $preco = $this->service->calcular($kit, 2, 'SP');

        $this->assertEquals(0.0, $preco['margem_total']);
        $this->assertEquals(20000.0, $preco['preco_venda']);
    }

    public function test_margens_sao_consultadas_uma_vez_independente_do_numero_de_kits(): void
    {
        $fornecedor = Fornecedor::create(['nome' => 'F', 'ativo' => true]);
        MargemPrincipal::create(['nome' => 'Padrão', 'potencia_min' => 0, 'potencia_max' => null, 'margem' => 20, 'ordem' => 1]);
        MargemEstado::create(['estado' => 'SP', 'nome_estado' => 'São Paulo', 'margem' => 2]);
        MargemFornecedor::create(['fornecedor_id' => $fornecedor->id, 'margem' => 1]);
        $kits = collect(range(1, 15))->map(fn ($kwp) => $this->kitSimples($fornecedor, $kwp));

        DB::enableQueryLog();
        $precos = $kits->map(fn ($kit) => $this->service->calcular($kit, 1, 'SP'));

        $this->assertCount(3, DB::getQueryLog());
        $this->assertTrue($precos->every(fn ($p) => $p['margem_total'] === 23.0));
    }
}
