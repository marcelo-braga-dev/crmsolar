<?php

namespace App\Http\Requests\Consultor\Dimensionamento;

/** Dimensionamento convencional (Grupo B), com opção de informar a potência em kWp. */
class ConvencionalRequest extends DimensionamentoRequest
{
    protected function regrasDoFluxo(): array
    {
        return [
            'tensao' => 'required|integer|in:127,220,380',
            'qtd_kits' => 'required|integer|min:1|max:10',
            'consumo' => 'required|numeric|min:0.1',
        ] + ($this->salvando()
            ? ['grupo_tarifario' => 'required|in:B1,B2,B3']
            : ['kwp_direto' => 'nullable|numeric|min:0.1']);
    }
}
