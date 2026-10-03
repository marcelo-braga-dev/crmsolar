<?php

namespace App\Services;

/**
 * Análise econômica de sistemas fotovoltaicos por grupo tarifário ANEEL.
 *
 * Implementa os cálculos de viabilidade financeira conforme:
 * - Lei 14.300/2022 (SIGA — Sistema de Geração Distribuída)
 * - Resolução ANEEL 1.000/2021
 *
 * Premissas dos cálculos:
 * - Compensação de energia kWh × kWh ao mesmo preço da tarifa vigente
 * - Custo de disponibilidade mantido mesmo com 100% de geração (Grupo B)
 * - Crescimento tarifário real histórico ANEEL: ~6% a.a. (conservador: 5%)
 * - Degradação anual dos painéis: 0,5% a.a. (primeiros anos) + 0,7% (após 10 anos)
 * - Vida útil do sistema: 25 anos
 * - Taxa de desconto mínima de atratividade: 8% a.a. (SELIC conservadora)
 */
class GrupoTarifarioService
{
    // ── Constantes de cálculo ─────────────────────────────────────────────

    private const VIDA_UTIL_ANOS = 25;

    private const CRESCIMENTO_TARIFA = 0.055;  // 5,5% a.a. crescimento tarifário conservador

    private const DEGRADACAO_ANUAL = 0.005;  // 0,5% a.a. degradação dos painéis

    private const TAXA_DESCONTO = 0.08;   // 8% a.a. — taxa mínima de atratividade

    // Custo de disponibilidade por tipo de ligação (Grupo B) — em kWh equivalentes
    private const DISPONIBILIDADE_KWH = [
        'monofasico' => 30,
        'bifasico' => 50,
        'trifasico' => 100,
    ];

    // ── Grupos Tarifários ────────────────────────────────────────────────

    public static function grupos(): array
    {
        return [
            'B1' => ['label' => 'B1 — Residencial',                'tensao' => 'BT', 'modalidades' => ['convencional']],
            'B2' => ['label' => 'B2 — Rural',                      'tensao' => 'BT', 'modalidades' => ['convencional']],
            'B3' => ['label' => 'B3 — Comercial / Industrial BT',  'tensao' => 'BT', 'modalidades' => ['convencional']],
            'A4' => ['label' => 'A4 — 2,3 kV a 25 kV (MT)',        'tensao' => 'MT', 'modalidades' => ['THS_VERDE', 'THS_AZUL']],
            'A3a' => ['label' => 'A3a — 30 kV a 44 kV (MT)',        'tensao' => 'MT', 'modalidades' => ['THS_VERDE', 'THS_AZUL']],
            'A3' => ['label' => 'A3 — 69 kV (AT)',                 'tensao' => 'AT', 'modalidades' => ['THS_VERDE', 'THS_AZUL']],
            'A2' => ['label' => 'A2 — 88 kV a 138 kV (AT)',        'tensao' => 'AT', 'modalidades' => ['THS_VERDE', 'THS_AZUL']],
            'A1' => ['label' => 'A1 — ≥ 230 kV (AT)',              'tensao' => 'AT', 'modalidades' => ['THS_VERDE', 'THS_AZUL']],
        ];
    }

    // ── Cálculo de economia mensal ────────────────────────────────────────

    /**
     * Calcula a economia mensal para Grupo B (B1, B2, B3).
     *
     * Lógica ANEEL (compensação de energia):
     *   Geração ≤ Consumo → economia = geração × tarifa
     *   Geração > Consumo → economia = consumo × tarifa (excedente vira crédito)
     *   Mas o custo de disponibilidade nunca é eliminado (obrigação mínima).
     *
     * @return array{economia_mensal: float, economia_anual: float, saldo_kwh: float, custo_residual: float}
     */
    public function economiaGrupoB(
        float $geracaoMensal,
        float $consumoMensal,
        float $tarifaKwh,
        string $fases = 'bifasico',
        int $objetivoPercentual = 100,
    ): array {
        $disponibilidadeKwh = self::DISPONIBILIDADE_KWH[$fases] ?? 50;
        $consumoAtendido = min($geracaoMensal, $consumoMensal * ($objetivoPercentual / 100));
        $saldoKwh = max(0, $geracaoMensal - $consumoMensal);

        // Quanto se economiza em R$ (limitado pelo consumo real - disponibilidade)
        $economizaKwh = max(0, $consumoAtendido - $disponibilidadeKwh);
        $economiaMensal = round($economizaKwh * $tarifaKwh, 2);

        // Custo residual que sempre vai existir (disponibilidade mínima)
        $custoResidual = round($disponibilidadeKwh * $tarifaKwh, 2);

        return [
            'economia_mensal' => $economiaMensal,
            'economia_anual' => round($economiaMensal * 12, 2),
            'saldo_kwh' => round($saldoKwh, 2),
            'custo_residual' => $custoResidual,
            'disponibilidade_kwh' => $disponibilidadeKwh,
            'consumo_atendido_kwh' => round($consumoAtendido, 2),
            'percentual_atendido' => $consumoMensal > 0
                ? round(($consumoAtendido / $consumoMensal) * 100, 1)
                : 0,
        ];
    }

    /**
     * Calcula a economia mensal para Grupo A (THS Verde ou Azul).
     *
     * No Grupo A a economia vem de:
     *   1. Redução do consumo de ponta (tarifa mais cara)
     *   2. Redução do consumo fora-ponta
     *   3. Possível redução de demanda (depende de como a geração coincide com a ponta)
     */
    public function economiaGrupoA(
        float $geracaoMensal,
        float $consumoPonta,
        float $consumoForaPonta,
        float $tarifaPonta,
        float $tarifaFP,
        float $percentualAutoconsumo = 80,
        string $modalidade = 'THS_VERDE',
    ): array {
        $totalConsumo = $consumoPonta + $consumoForaPonta;
        $geracaoAutoconsumo = $geracaoMensal * ($percentualAutoconsumo / 100);
        $geracaoInjetada = $geracaoMensal - $geracaoAutoconsumo;

        // Heurística de distribuição: geração prioriza fora-ponta (pior coincidência com ponta)
        // Na prática depende do perfil de geração vs. perfil de consumo
        $geracaoEmPonta = $geracaoAutoconsumo * 0.20; // ~20% da geração coincide com ponta
        $geracaoEmFP = $geracaoAutoconsumo - $geracaoEmPonta;

        $economiaConsumoPonta = min($geracaoEmPonta, $consumoPonta) * $tarifaPonta;
        $economiaConsumoFP = min($geracaoEmFP + $geracaoInjetada, $consumoForaPonta) * $tarifaFP;
        $economiaMensal = round($economiaConsumoPonta + $economiaConsumoFP, 2);

        return [
            'economia_mensal' => $economiaMensal,
            'economia_anual' => round($economiaMensal * 12, 2),
            'economia_ponta_mensal' => round($economiaConsumoPonta, 2),
            'economia_fp_mensal' => round($economiaConsumoFP, 2),
            'geracao_autoconsumo' => round($geracaoAutoconsumo, 2),
            'geracao_injetada' => round($geracaoInjetada, 2),
            'percentual_atendido' => $totalConsumo > 0
                ? round(($geracaoAutoconsumo / $totalConsumo) * 100, 1)
                : 0,
        ];
    }

    // ── Análise financeira completa ───────────────────────────────────────

    /**
     * Retorna a análise econômica completa de 25 anos.
     *
     * @param  float  $investimento  Valor total do sistema (R$)
     * @param  float  $economiaMensal  Economia mensal no ano 1 (R$)
     * @param  float  $crescimentoTarifa  Crescimento anual esperado da tarifa (padrão 5,5%)
     * @param  float  $degradacao  Degradação anual dos painéis (padrão 0,5%)
     * @param  float  $taxaDesconto  TMA — taxa mínima de atratividade (padrão 8%)
     */
    public function analiseCompleta(
        float $investimento,
        float $economiaMensal,
        float $crescimentoTarifa = self::CRESCIMENTO_TARIFA,
        float $degradacao = self::DEGRADACAO_ANUAL,
        float $taxaDesconto = self::TAXA_DESCONTO,
    ): array {
        $economiaAnualBase = $economiaMensal * 12;
        $fluxos = [];
        $vplAcumulado = -$investimento;
        $economiaTotal = 0;
        $paybackSimples = null;
        $paybackDescontado = null;
        $acumuladoSimples = -$investimento;

        for ($ano = 1; $ano <= self::VIDA_UTIL_ANOS; $ano++) {
            // Economia cresce com a tarifa, mas decresce com a degradação dos painéis
            $fatorCrescimento = (1 + $crescimentoTarifa) ** $ano;
            $fatorDegradacao = (1 - $degradacao) ** $ano;
            $economiaAno = $economiaAnualBase * $fatorCrescimento * $fatorDegradacao;

            // Fluxo descontado
            $fatorDesconto = 1 / ((1 + $taxaDesconto) ** $ano);
            $economiaDescontada = $economiaAno * $fatorDesconto;

            $vplAcumulado += $economiaDescontada;
            $economiaTotal += $economiaAno;
            $acumuladoSimples += $economiaAno;

            $fluxos[$ano] = [
                'economia' => round($economiaAno, 2),
                'descontada' => round($economiaDescontada, 2),
                'vpl' => round($vplAcumulado, 2),
                'acumulado' => round($economiaTotal, 2),
            ];

            if ($paybackSimples === null && $acumuladoSimples >= 0) {
                $paybackSimples = $ano;
            }
            if ($paybackDescontado === null && $vplAcumulado >= 0) {
                $paybackDescontado = $ano;
            }
        }

        $tir = $this->calcularTIR($investimento, array_column($fluxos, 'economia'));

        return [
            'investimento' => round($investimento, 2),
            'economia_mensal_ano1' => round($economiaMensal, 2),
            'economia_anual_ano1' => round($economiaAnualBase, 2),
            'economia_total_25a' => round($economiaTotal, 2),
            'vpl_25a' => round($vplAcumulado, 2),
            'tir_25a' => $tir,
            'payback_simples' => $paybackSimples ?? self::VIDA_UTIL_ANOS,
            'payback_descontado' => $paybackDescontado ?? null,
            'roi_percentual' => $investimento > 0
                ? round((($economiaTotal - $investimento) / $investimento) * 100, 1)
                : 0,
            'fluxos_anuais' => $fluxos,
            'premissas' => [
                'crescimento_tarifa' => $crescimentoTarifa * 100,
                'degradacao' => $degradacao * 100,
                'taxa_desconto' => $taxaDesconto * 100,
                'vida_util' => self::VIDA_UTIL_ANOS,
            ],
        ];
    }

    // ── B2 Rural — especificidades ────────────────────────────────────────

    /**
     * Calcula o tamanho do sistema para irrigação (bombeamento solar).
     * O dimensionamento de bomba solar não usa irradiação diária média,
     * mas sim o volume de água necessário e a altura manométrica.
     *
     * Fórmula: P (kWp) = (Q × Hm × γ × Fs) / (η_bomba × η_inv × HSP)
     * onde:
     *   Q   = vazão (m³/h)
     *   Hm  = altura manométrica total (m)
     *   γ   = peso específico da água (1000 kgf/m³)
     *   Fs  = fator de segurança (1.25)
     *   η_b = eficiência da bomba (~0.65)
     *   η_i = eficiência do inversor (~0.95)
     *   HSP = horas de sol pleno
     */
    public function calcularBombeamento(
        float $vazaoM3h,
        float $alturaManoMetrica,
        float $hsp,
        float $etaBomba = 0.65,
        float $etaInversor = 0.95,
    ): array {
        $potenciaW = ($vazaoM3h * $alturaManoMetrica * 1000 * 1.25 * 9.81)
            / ($etaBomba * $etaInversor * $hsp * 3600);

        $potenciaKwp = round($potenciaW / 1000, 3);

        return [
            'potencia_kwp' => $potenciaKwp,
            'vazao_m3h' => $vazaoM3h,
            'altura_m' => $alturaManoMetrica,
            'hsp' => $hsp,
        ];
    }

    // ── Grupo A — redução de demanda ─────────────────────────────────────

    /**
     * Estima a redução de demanda atingível com o sistema fotovoltaico.
     * A redução de demanda no Grupo A é limitada pela garantia de potência disponível.
     * Para efeitos de cálculo conservador: redução máxima de 30% da demanda de ponta.
     */
    public function reducaoDemanda(float $potenciaKwp, float $demandaAtualKw): array
    {
        // A potência fotovoltaica injetada na ponta é estimada como 85% da potência nominal
        // (condição de irradiância típica de 850 W/m² no horário de ponta de verão)
        $potenciaPonta = $potenciaKwp * 0.85;
        $reducaoKw = min($potenciaPonta, $demandaAtualKw * 0.30); // limite conservador de 30%

        return [
            'reducao_kw' => round($reducaoKw, 2),
            'nova_demanda_kw' => round($demandaAtualKw - $reducaoKw, 2),
            'percentual_reducao' => round(($reducaoKw / $demandaAtualKw) * 100, 1),
        ];
    }

    // ── Utilitários ───────────────────────────────────────────────────────

    /** Disponibilidade mínima em kWh conforme tipo de ligação (Grupo B). */
    public static function disponibilidadePorFase(string $fases): int
    {
        return self::DISPONIBILIDADE_KWH[$fases] ?? 50;
    }

    /**
     * Calcula a TIR (Taxa Interna de Retorno) usando o método de Newton-Raphson.
     * Retorna percentual anual arredondado.
     *
     * @param  float  $investimento  Custo inicial (positivo)
     * @param  float[]  $fluxos  Fluxos de caixa positivos por ano [ano1, ano2, ...]
     */
    private function calcularTIR(float $investimento, array $fluxos, int $maxIter = 100): float
    {
        $tir = 0.1; // chute inicial 10%

        for ($i = 0; $i < $maxIter; $i++) {
            $npv = -$investimento;
            $dnpv = 0;

            foreach ($fluxos as $t => $fc) {
                $ano = $t + 1;
                $npv += $fc / ((1 + $tir) ** $ano);
                $dnpv -= $ano * $fc / ((1 + $tir) ** ($ano + 1));
            }

            if (abs($dnpv) < 1e-10) {
                break;
            }

            $tirNova = $tir - $npv / $dnpv;
            if (abs($tirNova - $tir) < 1e-6) {
                $tir = $tirNova;
                break;
            }
            $tir = $tirNova;
        }

        return round($tir * 100, 2); // em %
    }
}
