<?php

namespace App\Http\Requests\Consultor\Dimensionamento;

/** Grupo B1 — Residencial. */
class GrupoB1Request extends DimensionamentoRequest
{
    protected function regrasDoFluxo(): array
    {
        return [
            'tensao' => 'required|integer|in:127,220',
            'qtd_kits' => 'required|integer|min:1|max:10',
            'fases' => 'required|in:monofasico,bifasico,trifasico',
            'consumo' => 'required|numeric|min:1',
            'tarifa_kwh' => 'required|numeric|min:0.01',
            'valor_conta_mensal' => 'nullable|numeric|min:0',
            'objetivo_percentual' => 'required|integer|in:50,75,100',
        ];
    }
}
