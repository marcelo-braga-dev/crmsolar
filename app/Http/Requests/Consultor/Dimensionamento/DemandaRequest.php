<?php

namespace App\Http\Requests\Consultor\Dimensionamento;

/** Dimensionamento por demanda (horo-sazonal), tarifas vindas da concessionária. */
class DemandaRequest extends DimensionamentoRequest
{
    protected function regrasDoFluxo(): array
    {
        return [
            'tensao' => 'required|integer|in:127,220,380',
            'qtd_kits' => 'required|integer|min:1|max:10',
            'consumo_ponta' => 'required|numeric|min:1',
            'consumo_fora_ponta' => 'required|numeric|min:1',
            'concessionaria_id' => 'required|exists:concessionarias,id',
        ] + ($this->salvando() ? ['grupo_tarifario' => 'required|in:A4,A3a,A3,A2,A1'] : []);
    }
}
