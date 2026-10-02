<?php

namespace Tests\Unit\Services;

use App\Services\DimensionamentoService;
use Tests\TestCase;

class DimensionamentoServiceTest extends TestCase
{
    private DimensionamentoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DimensionamentoService;
    }

    public function test_calcula_potencia_convencional_com_orientacao_norte(): void
    {
        $params = ['pr_sistema' => 0.80, 'margem' => 5];

        $potencia = $this->service->calcularPotencia(
            consumo: 500,
            hsp: 4.5,
            params: $params,
            orientacao: 'norte',
        );

        // P = (500/30) / (4.5 * 0.80) * 1.05
        $this->assertEquals(4.861, $potencia);
    }

    public function test_calcula_potencia_reduz_com_orientacao_desfavoravel(): void
    {
        $base = ['pr_sistema' => 0.80, 'margem' => 5];

        $norte = $this->service->calcularPotencia(500, 4.5, $base + ['perda_orient' => ['norte' => 0]], 'norte');
        $sul = $this->service->calcularPotencia(500, 4.5, $base + ['perda_orient' => ['sul' => 25]], 'sul');

        // Orientação sul tem mais perda → precisa de mais potência para gerar o mesmo consumo
        $this->assertGreaterThan($norte, $sul);
    }

    public function test_calcula_potencia_por_demanda_pondera_consumo_de_ponta(): void
    {
        $params = ['pr_sistema' => 0.80, 'margem' => 5];

        $potencia = $this->service->calcularPotenciaDemanda(
            consumoPonta: 100,
            consumoForaPonta: 400,
            tarifaPonta: 1.20,
            tarifaForaPonta: 0.60,
            hsp: 4.5,
            params: $params,
            orientacao: 'norte',
        );

        // fc = 1.20/0.60 = 2 → E_equiv = 400 + 2*100 = 600
        // P = (600/30) / (4.5*0.80) * 1.05
        $this->assertEquals(5.833, $potencia);
    }

    public function test_potencia_por_demanda_cresce_com_peso_da_tarifa_de_ponta(): void
    {
        $params = ['pr_sistema' => 0.80, 'margem' => 5];

        $baixoPeso = $this->service->calcularPotenciaDemanda(100, 400, 0.60, 0.60, 4.5, $params);
        $altoPeso = $this->service->calcularPotenciaDemanda(100, 400, 3.00, 0.60, 4.5, $params);

        $this->assertGreaterThan($baixoPeso, $altoPeso);
    }

    public function test_calcula_geracao_estimada(): void
    {
        $params = ['pr_sistema' => 0.80];

        $geracao = $this->service->calcularGeracao(
            hsp: 4.5,
            potenciaTotal: 5,
            params: $params,
            orientacao: 'norte',
        );

        // 5 * 4.5 * 30 * 0.80 = 540
        $this->assertSame(540, $geracao);
    }

    public function test_fator_orientacao_usa_perdas_configuradas_no_banco(): void
    {
        $params = ['perda_orient' => ['sudeste_sudoeste' => 10]];

        $fator = $this->service->fatorOrientacao('sudeste_sudoeste', $params);

        $this->assertEquals(0.90, $fator);
    }

    public function test_fator_orientacao_usa_constantes_padrao_sem_parametros_do_banco(): void
    {
        $fator = $this->service->fatorOrientacao('leste_oeste');

        $this->assertEquals(0.88, $fator);
    }

    public function test_detalhamento_pr_soma_perdas_de_sistema_e_orientacao(): void
    {
        $params = ['pr_sistema' => 0.80, 'margem' => 5, 'perda_orient' => ['sul' => 25]];

        $detalhe = $this->service->detalhamentoPR($params, 'sul');

        $this->assertEquals(80.0, $detalhe['pr_sistema']);
        $this->assertEquals(75.0, $detalhe['fator_orientacao']);
        $this->assertEquals(60.0, $detalhe['pr_total']);       // 0.80 * 0.75
        $this->assertEquals(5, $detalhe['margem_seguranca']);
        $this->assertEquals(20.0, $detalhe['perdas_sistema']);
        $this->assertEquals(25.0, $detalhe['perdas_orientacao']);
        $this->assertEquals(40.0, $detalhe['perdas_totais']);  // 1 - 0.60
    }
}
