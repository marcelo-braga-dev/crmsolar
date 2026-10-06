<?php

namespace Database\Seeders\Support;

/**
 * Sorteios determinísticos sobre mt_rand: com a semente fixada pelo MarketingDemoSeeder,
 * toda execução gera a mesma base.
 */
final class Sorteio
{
    public static function semente(int $semente): void
    {
        mt_srand($semente);
    }

    /** true com probabilidade $p (0–1). */
    public static function chance(float $p): bool
    {
        return mt_rand() / mt_getrandmax() < $p;
    }

    public static function entre(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    public static function decimal(float $min, float $max, int $casas = 2): float
    {
        return round($min + (mt_rand() / mt_getrandmax()) * ($max - $min), $casas);
    }

    /**
     * @template T
     *
     * @param  array<int|string, T>  $itens
     * @return T
     */
    public static function escolher(array $itens): mixed
    {
        $itens = array_values($itens);

        return $itens[mt_rand(0, count($itens) - 1)];
    }

    /**
     * Escolhe uma chave conforme o peso: ['a' => 3, 'b' => 1] sai "a" 75% das vezes.
     *
     * @param  array<int|string, int|float>  $pesos
     */
    public static function ponderado(array $pesos): int|string
    {
        $alvo = (mt_rand() / mt_getrandmax()) * array_sum($pesos);
        foreach ($pesos as $chave => $peso) {
            $alvo -= $peso;
            if ($alvo <= 0) {
                return $chave;
            }
        }

        return array_key_last($pesos);
    }

    /** Valor com ruído leve (±$pct), para nada ficar idêntico mês a mês. */
    public static function ruido(float $valor, float $pct = 0.10): float
    {
        return $valor * (1 + self::decimal(-$pct, $pct, 4));
    }

    /** Valor entre $min e $max concentrado perto da ponta baixa (poucos clientes muito grandes). */
    public static function assimetrico(float $min, float $max): float
    {
        $u = mt_rand() / mt_getrandmax();

        return $min + ($max - $min) * $u * $u;
    }
}
