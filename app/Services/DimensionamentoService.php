<?php

namespace App\Services;

use App\Models\IrradiacaoSolar;
use App\Models\Kit;
use App\Models\ParamDimensionamento;
use Illuminate\Database\Eloquent\Collection;

/**
 * Motor de dimensionamento de sistemas fotovoltaicos.
 *
 * Fórmula base (ABNT NBR 16690 / Atlas Solarimétrico INPE):
 *
 *   P_pico (kWp) = E_mensal / (HSP × 30 × PR_total)
 *
 * Onde:
 *   E_mensal  = consumo mensal em kWh/mês
 *   HSP       = Horas de Sol Pleno diárias (kWh/m²/dia) da cidade
 *   PR_total  = Performance Ratio total = PR_sistema × F_orientação
 *   30        = dias/mês (aproximação; pode usar 365/12 = 30.44)
 *
 * PR_sistema é composto pelas perdas configuráveis:
 *   - Perdas no cabeamento CC/CA (~2%)
 *   - Coeficiente de temperatura (~7% para BR, onde painéis ficam quentes)
 *   - Sujeira/sombreamento (~3%)
 *   - Mismatch entre painéis (~1%)
 *   - Eficiência do inversor (~3%)
 *   Total típico: 16–22% → PR_sistema = 0.78–0.84
 *
 * F_orientação reduz o PR conforme o desvio do Norte:
 *   Norte (0°) = 1.00 | NE/NO = 0.95 | L/O = 0.88 | SE/SO = 0.82 | Sul = 0.75
 */
class DimensionamentoService
{
    private const HSP_FALLBACK = 4.5;   // kWh/m²/dia (valor conservador médio BR)
    private const PR_FALLBACK  = 0.80;  // Performance Ratio padrão (20% perdas)

    private const ORIENTACAO_FATOR = [
        'norte'               => 1.00,
        'nordeste_noroeste'   => 0.95,
        'leste_oeste'         => 0.88,
        'sudeste_sudoeste'    => 0.82,
        'sul'                 => 0.75,
    ];

    // ── Parâmetros do banco ───────────────────────────────────────────────

    public function getParams(): array
    {
        $rows = ParamDimensionamento::all()->keyBy('chave');

        return [
            // PR do sistema (inverso do fator_perda): 20% perda → PR = 0.80
            'pr_sistema'    => 1 - ((float) ($rows['fator_perda_sistema']?->valor ?? 20)) / 100,
            'fator_perda'   => (float) ($rows['fator_perda_sistema']?->valor ?? 20),
            // Margem de segurança adicional aplicada sobre a potência calculada
            'margem'        => (float) ($rows['margem_seguranca']?->valor ?? 5),
            // Perdas por orientação (em %)
            'perda_orient'  => [
                'norte'             => (float) ($rows['perda_norte']?->valor             ?? 0),
                'nordeste_noroeste' => (float) ($rows['perda_nordeste_noroeste']?->valor  ?? 5),
                'leste_oeste'       => (float) ($rows['perda_leste_oeste']?->valor        ?? 12),
                'sudeste_sudoeste'  => (float) ($rows['perda_sudeste_sudoeste']?->valor   ?? 18),
                'sul'               => (float) ($rows['perda_sul']?->valor                ?? 25),
            ],
        ];
    }

    // ── Irradiação ────────────────────────────────────────────────────────

    public function getIrradiacao(int $cidadeId): float
    {
        return (float) (IrradiacaoSolar::where('cidade_id', $cidadeId)->value('media') ?? self::HSP_FALLBACK);
    }

    /** Retorna todos os valores mensais de irradiação para análise sazonal. */
    public function getIrradiacaoMensal(int $cidadeId): array
    {
        $row = IrradiacaoSolar::where('cidade_id', $cidadeId)->first();
        if (!$row) {
            return array_fill_keys(['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'], self::HSP_FALLBACK);
        }

        return [
            'jan' => (float) $row->jan, 'fev' => (float) $row->fev, 'mar' => (float) $row->mar,
            'abr' => (float) $row->abr, 'mai' => (float) $row->mai, 'jun' => (float) $row->jun,
            'jul' => (float) $row->jul, 'ago' => (float) $row->ago, 'set' => (float) $row->set,
            'out' => (float) $row->out, 'nov' => (float) $row->nov, 'dez' => (float) $row->dez,
        ];
    }

    // ── Fator de orientação ───────────────────────────────────────────────

    public function fatorOrientacao(string $orientacao, array $params = []): float
    {
        if ($params) {
            $perda = $params['perda_orient'][$orientacao] ?? 0;
            return 1 - ($perda / 100);
        }

        return self::ORIENTACAO_FATOR[$orientacao] ?? 1.0;
    }

    // ── Dimensionamento Convencional ──────────────────────────────────────

    /**
     * Calcula a potência de pico necessária (kWp) — dimensionamento convencional.
     *
     * P_pico = E_mensal / (HSP × 30 × PR_total × F_margem)
     *
     * PR_total = PR_sistema × F_orientação
     * F_margem = 1 / (1 + margem/100)  →  garante que o sistema cubra o consumo
     *            mesmo com margem de segurança.
     */
    public function calcularPotencia(
        float $consumo,
        float $hsp,
        array $params,
        string $orientacao = 'norte',
    ): float {
        $fOrient  = $this->fatorOrientacao($orientacao, $params);
        $prTotal  = $params['pr_sistema'] * $fOrient;
        $fMargem  = 1 + ($params['margem'] / 100);   // ex.: 5% → fator 1.05

        $potencia = ($consumo / 30) / ($hsp * $prTotal);

        return round($potencia * $fMargem, 3);
    }

    // ── Dimensionamento por Demanda (horo-sazonal — Grupo A) ─────────────

    /**
     * Dimensionamento para clientes com tarifação horo-sazonal.
     *
     * Princípio: cada kWh gerado no horário de ponta economiza mais
     * (tarifa_ponta >> tarifa_fora_ponta), então ponderamos o consumo
     * de ponta pelo fator de custo (fc = T_ponta / T_fora_ponta).
     *
     *   E_equiv = E_fora_ponta + fc × E_ponta
     *   P_pico  = E_equiv / (HSP × 30 × PR_total × F_margem)
     */
    public function calcularPotenciaDemanda(
        float $consumoPonta,
        float $consumoForaPonta,
        float $tarifaPonta,
        float $tarifaForaPonta,
        float $hsp,
        array $params,
        string $orientacao = 'norte',
    ): float {
        $fc          = $tarifaPonta / max($tarifaForaPonta, 0.0001);
        $eEquiv      = $consumoForaPonta + ($fc * $consumoPonta);

        $fOrient     = $this->fatorOrientacao($orientacao, $params);
        $prTotal     = $params['pr_sistema'] * $fOrient;
        $fMargem     = 1 + ($params['margem'] / 100);

        $potencia    = ($eEquiv / 30) / ($hsp * $prTotal);

        return round($potencia * $fMargem, 3);
    }

    // ── Geração estimada ─────────────────────────────────────────────────

    /**
     * Calcula a geração mensal estimada (kWh/mês) para a potência instalada.
     *
     * E_mensal = P_pico × HSP × 30 × PR_total
     *
     * Nota: usa PR_total sem o fator de margem de segurança,
     * pois a margem apenas aumenta o sistema — a geração real é baseada
     * no PR físico do sistema.
     */
    public function calcularGeracao(
        float $hsp,
        float $potenciaTotal,
        array $params,
        string $orientacao = 'norte',
    ): int {
        $fOrient = $this->fatorOrientacao($orientacao, $params);
        $prTotal = $params['pr_sistema'] * $fOrient;

        return (int) round($potenciaTotal * $hsp * 30 * $prTotal);
    }

    // ── Análise mensal (cobertura por mês) ───────────────────────────────

    /**
     * Analisa a geração mês a mês e a cobertura do consumo.
     * Fundamental para identificar o pior mês e comunicar ao cliente.
     *
     * @return array{
     *   meses: array,
     *   pior_mes: string,
     *   pior_cobertura: float,
     *   melhor_mes: string,
     *   melhor_cobertura: float,
     *   geracao_anual: float,
     *   consumo_anual: float,
     * }
     */
    public function analiseMensal(
        int $cidadeId,
        float $potenciaTotal,
        float $consumoMensal,
        array $params,
        string $orientacao = 'norte',
    ): array {
        $irradMensal = $this->getIrradiacaoMensal($cidadeId);
        $fOrient     = $this->fatorOrientacao($orientacao, $params);
        $prTotal     = $params['pr_sistema'] * $fOrient;

        $diasMes = ['jan'=>31,'fev'=>28,'mar'=>31,'abr'=>30,'mai'=>31,'jun'=>30,'jul'=>31,'ago'=>31,'set'=>30,'out'=>31,'nov'=>30,'dez'=>31];

        $meses = [];
        foreach ($diasMes as $mes => $dias) {
            $hsp     = $irradMensal[$mes] ?? self::HSP_FALLBACK;
            $geracao = round($potenciaTotal * $hsp * $dias * $prTotal);
            $cobertura = $consumoMensal > 0 ? round(($geracao / $consumoMensal) * 100, 1) : 0;

            $meses[$mes] = [
                'hsp'       => $hsp,
                'geracao'   => $geracao,
                'consumo'   => $consumoMensal,
                'cobertura' => $cobertura,
            ];
        }

        $coberturas   = array_column($meses, 'cobertura');
        $mesNomes     = array_keys($meses);
        $idxPior      = array_search(min($coberturas), $coberturas);
        $idxMelhor    = array_search(max($coberturas), $coberturas);

        return [
            'meses'           => $meses,
            'pior_mes'        => $mesNomes[$idxPior],
            'pior_cobertura'  => min($coberturas),
            'melhor_mes'      => $mesNomes[$idxMelhor],
            'melhor_cobertura'=> max($coberturas),
            'geracao_anual'   => array_sum(array_column($meses, 'geracao')),
            'consumo_anual'   => $consumoMensal * 12,
        ];
    }

    // ── Busca de kits ────────────────────────────────────────────────────

    /**
     * Busca kits compatíveis para a potência calculada.
     * Com qtdKits > 1 divide a potência por kit e busca cada faixa individualmente.
     */
    /**
     * @param array<string> $categorias filtro de categorias — ex: ['ongrid','hibrido']. Vazio = todas.
     */
    public function buscarKits(
        float $potenciaKwp,
        int   $estruturaId,
        int   $tensao,
        int   $qtdKits = 1,
        array $categorias = [],
    ): Collection {
        $potenciaPorKit = $potenciaKwp / max($qtdKits, 1);
        [$min, $max]    = $this->tolerancia($potenciaPorKit);

        return Kit::query()
            ->where('estrutura_id', $estruturaId)
            ->where('tensao', $tensao)
            ->whereBetween('potencia_kwp', [$min, $max])
            ->where('ativo', true)
            ->where('ativo_fornecedor', true)
            ->when(!empty($categorias), fn($q) => $q->whereIn('categoria', $categorias))
            ->with('fornecedor:id,nome')
            ->orderBy('preco_custo')
            ->get();
    }

    // ── Detalhamento do PR ────────────────────────────────────────────────

    /**
     * Retorna um resumo explicativo do PR total para exibir na proposta.
     */
    public function detalhamentoPR(array $params, string $orientacao): array
    {
        $fOrient  = $this->fatorOrientacao($orientacao, $params);
        $prSist   = $params['pr_sistema'];
        $prTotal  = $prSist * $fOrient;
        $margem   = $params['margem'];

        return [
            'pr_sistema'          => round($prSist * 100, 1),
            'fator_orientacao'    => round($fOrient * 100, 1),
            'pr_total'            => round($prTotal * 100, 1),
            'margem_seguranca'    => $margem,
            'perdas_sistema'      => round((1 - $prSist) * 100, 1),
            'perdas_orientacao'   => round((1 - $fOrient) * 100, 1),
            'perdas_totais'       => round((1 - $prTotal) * 100, 1),
        ];
    }

    // ── Tolerância de busca ───────────────────────────────────────────────

    /**
     * Faixa de tolerância para busca de kits.
     * Sistemas maiores aceitam menor variação pois o catálogo é mais denso.
     */
    private function tolerancia(float $potencia): array
    {
        $variacao = match (true) {
            $potencia > 50 => 2,
            $potencia > 20 => 3,
            $potencia > 10 => 5,
            $potencia > 3  => 8,
            $potencia > 1  => 10,
            default        => 15,
        };

        return [
            round($potencia * (1 - $variacao / 100), 3),
            round($potencia * (1 + $variacao / 100), 3),
        ];
    }
}
