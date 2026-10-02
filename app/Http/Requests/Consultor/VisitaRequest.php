<?php

namespace App\Http\Requests\Consultor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visita = $this->route('visita');

        return $visita ? $this->user()->can('update', $visita) : true;
    }

    public function rules(): array
    {
        // Na edição não se troca o cliente/orçamento vinculado, só reagenda ou muda o status.
        if ($this->route('visita')) {
            return [
                'data_agendada' => 'required|date',
                'status' => 'required|in:agendada,realizada,cancelada',
                'anotacoes' => 'nullable|string|max:2000',
            ];
        }

        return [
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where('consultor_id', $this->user()->id),
            ],
            'orcamento_id' => [
                'nullable',
                Rule::exists('orcamentos', 'id')->where('consultor_id', $this->user()->id),
            ],
            'data_agendada' => 'required|date',
            'anotacoes' => 'nullable|string|max:2000',
        ];
    }
}
