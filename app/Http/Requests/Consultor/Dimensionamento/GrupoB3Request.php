<?php

namespace App\Http\Requests\Consultor\Dimensionamento;

/** Grupo B3 — Comercial / Industrial em baixa tensão. */
class GrupoB3Request extends DimensionamentoRequest
{
    protected function regrasDoFluxo(): array
    {
        return [
            'tensao' => 'required|integer|in:127,220,380',
            'qtd_kits' => 'required|integer|min:1|max:20',
            'fases' => 'required|in:bifasico,trifasico',
            'consumo' => 'required|numeric|min:1',
            'tarifa_kwh' => 'required|numeric|min:0.01',
            'valor_conta_mensal' => 'nullable|numeric|min:0',
            'objetivo_percentual' => 'required|integer|in:50,75,100',
            'horario_funcionamento' => 'required|integer|min:4|max:24',
            'percentual_autoconsumo' => 'required|integer|min:10|max:100',
        ];
    }
}
