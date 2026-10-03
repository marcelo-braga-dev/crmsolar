<?php

namespace App\Services;

use App\Models\Kit;
use App\Models\MargemEstado;
use App\Models\MargemFornecedor;
use App\Models\MargemPrincipal;
use Illuminate\Support\Collection;

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

        $mp = $this->margemPrincipal($potenciaTotal);
        $mes = $this->margemEstado($estado);
        $mf = $this->margemFornecedor($kit->fornecedor_id);

        $margemTotal = $mp + $mes + $mf;
        $precoCusto = (float) $kit->preco_custo * $qtdKits;
        $precoVenda = round($precoCusto * (1 + $margemTotal / 100), 2);

        return [
            'margem_principal' => $mp,
            'margem_estado' => $mes,
            'margem_fornecedor' => $mf,
            'margem_total' => $margemTotal,
            'preco_custo' => $precoCusto,
            'preco_venda' => $precoVenda,
        ];
    }

    /** Margens carregadas uma vez por instância (uma requisição) — antes eram 3 queries por kit. */
    private ?Collection $faixas = null;

    private ?Collection $margensEstado = null;

    private ?Collection $margensFornecedor = null;

    private function margemPrincipal(float $potenciaTotal): float
    {
        $this->faixas ??= MargemPrincipal::orderBy('potencia_min')->get(['potencia_min', 'potencia_max', 'margem']);

        // Primeira faixa onde potencia_min ≤ total ≤ potencia_max (null = sem limite superior)
        $faixa = $this->faixas->first(fn ($f) => (float) $f->potencia_min <= $potenciaTotal
            && ($f->potencia_max === null || (float) $f->potencia_max >= $potenciaTotal))
            // Fora de qualquer faixa (lacuna entre faixas ou acima de todas): última faixa que começa
            // abaixo da potência — nunca salta para a faixa mais alta, que costuma ter a menor margem.
            ?? $this->faixas->last(fn ($f) => (float) $f->potencia_min <= $potenciaTotal)
            // Abaixo da primeira faixa: a de menor potência.
            ?? $this->faixas->first();

        return (float) ($faixa->margem ?? 0);
    }

    private function margemEstado(string $estado): float
    {
        $this->margensEstado ??= MargemEstado::pluck('margem', 'estado');

        return (float) ($this->margensEstado[$estado] ?? 0);
    }

    private function margemFornecedor(int $fornecedorId): float
    {
        $this->margensFornecedor ??= MargemFornecedor::pluck('margem', 'fornecedor_id');

        return (float) ($this->margensFornecedor[$fornecedorId] ?? 0);
    }
}
