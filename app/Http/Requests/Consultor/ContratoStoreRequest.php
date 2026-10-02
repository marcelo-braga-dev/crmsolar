<?php

namespace App\Http\Requests\Consultor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContratoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orcamento_id' => [
                'required',
                Rule::exists('orcamentos', 'id')->where('consultor_id', $this->user()->id),
            ],
            'nome_cliente' => 'required|string|max:255',
            'documento_cliente' => 'required|string|max:20',
            'endereco_instalacao' => 'required|string|max:500',
            'potencia_kwp' => 'required|numeric|min:0',
            'qtd_paineis' => 'required|integer|min:0',
            'qtd_inversores' => 'required|integer|min:0',
            'modelo_inversor' => 'required|string|max:255',
            'consumo_mensal' => 'required|integer|min:0',
            'geracao_estimada' => 'required|integer|min:0',
            'garantia_paineis' => 'required|string|max:255',
            'garantia_inversores' => 'required|string|max:255',
            'formas_pagamento' => 'required|string',
            'clausulas_adicionais' => 'nullable|string',
        ];
    }
}
