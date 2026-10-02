<?php

namespace App\Http\Requests\Consultor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropostaServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proposta = $this->route('proposta_servico');

        return $proposta ? $this->user()->can('update', $proposta) : true;
    }

    public function rules(): array
    {
        $editando = (bool) $this->route('proposta_servico');

        return [
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where('consultor_id', $this->user()->id),
            ],
            'titulo' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
            'validade' => $editando ? 'required|date' : 'required|date|after_or_equal:today',
            'conteudo' => 'required|string|min:10',
            'observacoes' => 'nullable|string|max:3000',
            'status' => $editando
                ? 'required|in:rascunho,enviada,aceita,recusada,expirada'
                : 'required|in:rascunho,enviada',
        ];
    }
}
