<?php

namespace App\Services;

use App\Models\Kit;
use App\Models\MargemEstado;
use App\Models\MargemFornecedor;
use App\Models\MargemPrincipal;

class PrecificacaoService
{
    /**
     * Calcula preço de venda com as 3 camadas de margem.
     * preco_venda = preco_custo × qtd × (1 + soma_margens / 100)
     *
     * @return array{margem_principal: float, margem_estado: float, margem_fornecedor: float,
     *               margem_total: float, preco_custo: float, preco_venda: float}
     */
    public function calcular(Kit $kit, int $qtdKits, string $estado): array
    {
        $potenciaTotal = (float) $kit->potencia_kwp * $qtdKits;

        $mp  = $this->margemPrincipal($potenciaTotal);
        $mes = $this->margemEstado($estado);
        $mf  = $this->margemFornecedor($kit->fornecedor_id);

        $margemTotal = $mp + $mes + $mf;
        $precoCusto  = (float) $kit->preco_custo * $qtdKits;
        $precoVenda  = round($precoCusto * (1 + $margemTotal / 100), 2);

        return [
            'margem_principal'  => $mp,
            'margem_estado'     => $mes,
            'margem_fornecedor' => $mf,
            'margem_total'      => $margemTotal,
            'preco_custo'       => $precoCusto,
            'preco_venda'       => $precoVenda,
        ];
    }

    private function margemPrincipal(float $potenciaTotal): float
    {
        // Primeira faixa onde potencia_min ≤ total ≤ potencia_max (null = sem limite superior)
        $margem = MargemPrincipal::query()
            ->orderBy('potencia_min')
            ->where('potencia_min', '<=', $potenciaTotal)
            ->where(fn ($q) => $q->whereNull('potencia_max')->orWhere('potencia_max', '>=', $potenciaTotal))
            ->value('margem');

        if ($margem !== null) {
            return (float) $margem;
        }

        // Fallback: faixa mais alta cadastrada
        return (float) (MargemPrincipal::orderByDesc('potencia_min')->value('margem') ?? 0);
    }

    private function margemEstado(string $estado): float
    {
        return (float) (MargemEstado::where('estado', $estado)->value('margem') ?? 0);
    }

    private function margemFornecedor(int $fornecedorId): float
    {
        return (float) (MargemFornecedor::where('fornecedor_id', $fornecedorId)->value('margem') ?? 0);
    }
}
