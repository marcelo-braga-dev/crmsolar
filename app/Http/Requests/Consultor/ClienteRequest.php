<?php

namespace App\Http\Requests\Consultor;

use Illuminate\Foundation\Http\FormRequest;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cliente = $this->route('cliente');

        return $cliente ? $this->user()->can('update', $cliente) : true;
    }

    public function rules(): array
    {
        return [
            'cidade_id' => 'nullable|exists:cidades_estados,id',
            'tipo_pessoa' => 'required|in:pf,pj',
            'nome' => 'nullable|string|max:255',
            'razao_social' => 'nullable|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'cnpj' => 'nullable|string|max:18',
            'rg' => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:9',
            'rua' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'status' => 'required|in:novo,orcamento_gerado,visita_agendada,finalizado',
            'anotacoes' => 'nullable|string',
        ];
    }
}
