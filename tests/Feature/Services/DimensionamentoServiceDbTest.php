<?php

namespace Tests\Feature\Services;

use App\Models\CidadeEstado;
use App\Models\Estrutura;
use App\Models\Fornecedor;
use App\Models\IrradiacaoSolar;
use App\Models\Kit;
use App\Models\ParamDimensionamento;
use App\Services\DimensionamentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DimensionamentoServiceDbTest extends TestCase
{
    use RefreshDatabase;

    private DimensionamentoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DimensionamentoService;
    }

    public function test_get_params_usa_fallback_quando_nao_ha_configuracao(): void
    {
        $params = $this->service->getParams();

        $this->assertEquals(0.80, $params['pr_sistema']); // fallback: 20% de perda
        $this->assertEquals(5.0, $params['margem']);
        $this->assertEquals(12, $params['perda_orient']['leste_oeste']);
    }

    public function test_get_params_usa_valores_configurados_pelo_admin(): void
    {
        ParamDimensionamento::create(['chave' => 'fator_perda_sistema', 'nome' => 'Perda do sistema', 'valor' => '15']);
        ParamDimensionamento::create(['chave' => 'margem_seguranca', 'nome' => 'Margem', 'valor' => '10']);

        $params = $this->service->getParams();

        $this->assertEquals(0.85, $params['pr_sistema']); // 1 - 15/100
        $this->assertEquals(10.0, $params['margem']);
    }

    public function test_get_irradiacao_retorna_media_da_cidade(): void
    {
        $cidade = CidadeEstado::create(['cidade' => 'Fortaleza', 'estado' => 'Ceará', 'sigla' => 'CE']);
        IrradiacaoSolar::create([
            'cidade_id' => $cidade->id, 'media' => 5.432,
            'jan' => 5.5, 'fev' => 5.5, 'mar' => 5.4, 'abr' => 5.3, 'mai' => 5.2, 'jun' => 5.1,
            'jul' => 5.2, 'ago' => 5.4, 'set' => 5.6, 'out' => 5.6, 'nov' => 5.5, 'dez' => 5.5,
        ]);

        $this->assertEquals(5.432, $this->service->getIrradiacao($cidade->id));
    }

    public function test_get_irradiacao_cai_no_fallback_para_cidade_sem_dados(): void
    {
        $this->assertEquals(4.5, $this->service->getIrradiacao(999999));
    }

    public function test_get_irradiacao_mensal_retorna_fallback_para_cidade_desconhecida(): void
    {
        $mensal = $this->service->getIrradiacaoMensal(999999);

        $this->assertCount(12, $mensal);
        $this->assertEquals(4.5, $mensal['jan']);
        $this->assertEquals(4.5, $mensal['dez']);
    }

    public function test_busca_kits_respeita_estrutura_tensao_e_faixa_de_potencia(): void
    {
        $fornecedor = Fornecedor::create(['nome' => 'Fornecedor Solar', 'ativo' => true]);
        $estrutura = Estrutura::create(['nome' => 'Telhado cerâmico', 'ativo' => true]);
        $outraEstrutura = Estrutura::create(['nome' => 'Solo', 'ativo' => true]);

        $compativel = Kit::create([
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id,
            'nome' => 'Kit 5kWp', 'potencia_kwp' => 5.0, 'tensao' => 220,
            'preco_custo' => 15000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);
        Kit::create([ // fora da faixa de tolerância (potência muito diferente)
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id,
            'nome' => 'Kit 20kWp', 'potencia_kwp' => 20.0, 'tensao' => 220,
            'preco_custo' => 40000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);
        Kit::create([ // estrutura errada
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $outraEstrutura->id,
            'nome' => 'Kit 5kWp solo', 'potencia_kwp' => 5.0, 'tensao' => 220,
            'preco_custo' => 14000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);
        Kit::create([ // inativo
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id,
            'nome' => 'Kit 5kWp inativo', 'potencia_kwp' => 5.0, 'tensao' => 220,
            'preco_custo' => 13000, 'ativo' => false, 'ativo_fornecedor' => true,
        ]);

        $resultado = $this->service->buscarKits(potenciaKwp: 5.0, estruturaId: $estrutura->id, tensao: 220);

        $this->assertCount(1, $resultado);
        $this->assertTrue($resultado->contains('id', $compativel->id));
    }

    public function test_busca_kits_filtra_por_categoria_quando_informado(): void
    {
        $fornecedor = Fornecedor::create(['nome' => 'Fornecedor Solar', 'ativo' => true]);
        $estrutura = Estrutura::create(['nome' => 'Telhado metálico', 'ativo' => true]);

        Kit::create([
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id,
            'nome' => 'Kit ongrid', 'categoria' => 'ongrid', 'potencia_kwp' => 5.0, 'tensao' => 220,
            'preco_custo' => 15000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);
        Kit::create([
            'fornecedor_id' => $fornecedor->id, 'estrutura_id' => $estrutura->id,
            'nome' => 'Kit híbrido', 'categoria' => 'hibrido', 'potencia_kwp' => 5.0, 'tensao' => 220,
            'preco_custo' => 16000, 'ativo' => true, 'ativo_fornecedor' => true,
        ]);

        $resultado = $this->service->buscarKits(5.0, $estrutura->id, 220, categorias: ['hibrido']);

        $this->assertCount(1, $resultado);
        $this->assertSame('hibrido', $resultado->first()->categoria);
    }
}
