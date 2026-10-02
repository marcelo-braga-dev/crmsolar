<?php

namespace Tests\Unit\Services;

use App\Services\GrupoTarifarioService;
use Tests\TestCase;

class GrupoTarifarioServiceTest extends TestCase
{
    private GrupoTarifarioService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GrupoTarifarioService;
    }

    public function test_economia_grupo_b_desconta_disponibilidade_minima(): void
    {
        $economia = $this->service->economiaGrupoB(
            geracaoMensal: 600,
            consumoMensal: 500,
            tarifaKwh: 0.90,
            fases: 'bifasico', // disponibilidade mínima = 50 kWh
            objetivoPercentual: 100,
        );

        $this->assertEquals(405.0, $economia['economia_mensal']);   // (500 - 50) * 0.90
        $this->assertEquals(4860.0, $economia['economia_anual']);
        $this->assertEquals(100.0, $economia['saldo_kwh']);          // 600 - 500 geração excedente
        $this->assertEquals(45.0, $economia['custo_residual']);      // 50 * 0.90
        $this->assertEquals(100.0, $economia['percentual_atendido']);
    }

    public function test_economia_grupo_b_nunca_elimina_custo_de_disponibilidade(): void
    {
        // Geração muito maior que o consumo: mesmo assim a disponibilidade mínima permanece.
        $economia = $this->service->economiaGrupoB(
            geracaoMensal: 2000,
            consumoMensal: 100,
            tarifaKwh: 1.00,
            fases: 'trifasico', // disponibilidade mínima = 100 kWh
        );

        // consumoAtendido = min(2000, 100) = 100; economizaKwh = max(0, 100-100) = 0
        $this->assertEquals(0.0, $economia['economia_mensal']);
        $this->assertEquals(100.0, $economia['custo_residual']);
    }

    public function test_economia_grupo_a_pondera_geracao_entre_ponta_e_fora_ponta(): void
    {
        $economia = $this->service->economiaGrupoA(
            geracaoMensal: 600,
            consumoPonta: 100,
            consumoForaPonta: 400,
            tarifaPonta: 1.50,
            tarifaFP: 0.60,
            percentualAutoconsumo: 80,
            modalidade: 'THS_VERDE',
        );

        $this->assertEquals(384.0, $economia['economia_mensal']);
        $this->assertEquals(144.0, $economia['economia_ponta_mensal']);
        $this->assertEquals(240.0, $economia['economia_fp_mensal']);
        $this->assertEquals(480.0, $economia['geracao_autoconsumo']);
        $this->assertEquals(120.0, $economia['geracao_injetada']);
        $this->assertEquals(96.0, $economia['percentual_atendido']);
    }

    public function test_analise_completa_devolve_25_anos_de_fluxo_consistentes_com_o_payback(): void
    {
        $analise = $this->service->analiseCompleta(
            investimento: 10000,
            economiaMensal: 250,
        );

        $this->assertCount(25, $analise['fluxos_anuais']);

        $payback = $analise['payback_simples'];
        $this->assertGreaterThanOrEqual(1, $payback);
        $this->assertLessThanOrEqual(25, $payback);

        // Invariante do payback: acumulado de economia atinge o investimento
        // exatamente no ano apontado (e não antes).
        $this->assertGreaterThanOrEqual(
            $analise['investimento'],
            $analise['fluxos_anuais'][$payback]['acumulado'],
        );
        if ($payback > 1) {
            $this->assertLessThan(
                $analise['investimento'],
                $analise['fluxos_anuais'][$payback - 1]['acumulado'],
            );
        }

        // ROI é consistente com o total de economia acumulada em 25 anos.
        $roiEsperado = round(
            (($analise['economia_total_25a'] - $analise['investimento']) / $analise['investimento']) * 100,
            1,
        );
        $this->assertEquals($roiEsperado, $analise['roi_percentual']);

        // Sistema com retorno positivo dentro da vida útil deve ter TIR > 0.
        $this->assertGreaterThan(0, $analise['tir_25a']);
    }

    public function test_analise_completa_nao_divide_por_zero_com_investimento_zero(): void
    {
        $analise = $this->service->analiseCompleta(investimento: 0, economiaMensal: 100);

        $this->assertEquals(0, $analise['roi_percentual']);
    }

    public function test_calcula_bombeamento_solar(): void
    {
        $bomba = $this->service->calcularBombeamento(
            vazaoM3h: 10,
            alturaManoMetrica: 20,
            hsp: 5,
        );

        $this->assertEquals(0.221, $bomba['potencia_kwp']);
    }

    public function test_reducao_de_demanda_e_limitada_a_30_por_cento(): void
    {
        $reducao = $this->service->reducaoDemanda(potenciaKwp: 100, demandaAtualKw: 50);

        // potência na ponta = 85, mas o limite conservador é 30% da demanda (15 kW)
        $this->assertEquals(15.0, $reducao['reducao_kw']);
        $this->assertEquals(35.0, $reducao['nova_demanda_kw']);
        $this->assertEquals(30.0, $reducao['percentual_reducao']);
    }

    public function test_disponibilidade_por_fase(): void
    {
        $this->assertSame(30, GrupoTarifarioService::disponibilidadePorFase('monofasico'));
        $this->assertSame(50, GrupoTarifarioService::disponibilidadePorFase('bifasico'));
        $this->assertSame(100, GrupoTarifarioService::disponibilidadePorFase('trifasico'));
        $this->assertSame(50, GrupoTarifarioService::disponibilidadePorFase('desconhecido')); // fallback
    }
}
