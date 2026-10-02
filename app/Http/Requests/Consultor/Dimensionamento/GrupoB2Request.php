<?php

namespace App\Http\Requests\Consultor\Dimensionamento;

/** Grupo B2 — Rural: regras do B1 + tipo de instalação e bombeamento. */
class GrupoB2Request extends GrupoB1Request
{
    protected function regrasDoFluxo(): array
    {
        return parent::regrasDoFluxo() + [
            'tipo_instalacao' => 'required|in:residencial_rural,produtivo,irrigacao',
            'possui_bombeamento' => 'required|boolean',
            'consumo_bombeamento' => 'nullable|numeric|min:0',
        ];
    }
}
