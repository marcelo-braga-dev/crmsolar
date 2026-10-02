<?php

namespace App\Http\Requests\Consultor;

use Illuminate\Foundation\Http\FormRequest;

class LeadUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:novo,contatado,encaminhado,convertido,perdido',
            'anotacoes' => 'nullable|string',
        ];
    }
}
