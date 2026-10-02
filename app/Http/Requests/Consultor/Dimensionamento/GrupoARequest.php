<?php

namespace App\Http\Requests\Consultor\Dimensionamento;

/** Grupo A — Média e Alta Tensão, modalidades horo-sazonais Verde e Azul. */
class GrupoARequest extends DimensionamentoRequest
{
    protected function regrasDoFluxo(): array
    {
        return [
            'tensao' => 'required|integer|in:220,380',
            'qtd_kits' => 'required|integer|min:1|max:30',
            'grupo_tarifario' => 'required|in:A4,A3a,A3,A2,A1',
            'modalidade_tarifaria' => 'required|in:THS_VERDE,THS_AZUL',
            'consumo_ponta' => 'required|numeric|min:1',
            'consumo_fora_ponta' => 'required|numeric|min:1',
            'tarifa_kwh_ponta' => 'required|numeric|min:0.01',
            'tarifa_kwh_fp' => 'required|numeric|min:0.01',
            'demanda_ponta_kw' => 'required|numeric|min:1',
            // THS Azul cobra demanda de ponta e fora de ponta separadamente.
            'demanda_fora_ponta_kw' => 'nullable|required_if:modalidade_tarifaria,THS_AZUL|numeric|min:1',
            'tarifa_demanda_ponta' => 'required|numeric|min:0',
            'tarifa_demanda_fp' => 'nullable|required_if:modalidade_tarifaria,THS_AZUL|numeric|min:0',
            'valor_conta_mensal' => 'nullable|numeric|min:0',
            'percentual_autoconsumo' => 'required|integer|min:10|max:100',
            'subgrupo_tensao' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'demanda_fora_ponta_kw.required_if' => 'THS Azul exige a demanda fora de ponta.',
            'tarifa_demanda_fp.required_if' => 'THS Azul exige a tarifa de demanda fora de ponta.',
        ];
    }
}
