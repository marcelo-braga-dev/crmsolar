<?php

namespace App\Http\Resources;

use App\Models\Orcamento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Proposta exibida via link público (token). Whitelist explícita:
 * nunca expor documentos do cliente, custo, margem, comissão ou anotações internas.
 *
 * @mixin Orcamento
 */
class OrcamentoPublicoResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'grupo_tarifario' => $this->grupo_tarifario,
            'modalidade_tarifaria' => $this->modalidade_tarifaria,
            'preco_total' => (float) $this->preco_total,
            'geracao_estimada' => $this->geracao_estimada,
            'created_at' => $this->created_at?->toIso8601String(),
            'cliente' => [
                'nome' => $this->cliente?->nome_display,
                'cidade' => $this->cidade?->cidade,
                'estado' => $this->cidade?->estado,
            ],
            'consultor' => [
                'nome' => $this->consultor?->name,
                'email' => $this->consultor?->email,
                'celular' => $this->consultor?->celular,
            ],
            'info' => $this->info ? [
                'consumo' => $this->info->consumo,
                'tensao' => $this->info->tensao,
                'fases' => $this->info->fases,
                'orientacao' => $this->info->orientacao,
                'estrutura' => $this->info->estrutura?->nome,
                'analise_economica' => $this->info->analise_economica,
            ] : null,
            'itens' => $this->itens->sortBy('ordem')->values()->map(fn ($item) => [
                'tipo' => $item->tipo,
                'descricao' => $item->descricao,
                'quantidade' => $item->quantidade,
                'preco_venda_unitario' => (float) $item->preco_venda_unitario,
                'preco_venda_total' => (float) $item->preco_venda_total,
                'geracao_estimada' => $item->geracao_estimada,
                'potencia_kwp' => $item->metadados['potencia_kwp'] ?? null,
            ]),
        ];
    }
}
