<?php

namespace App\Http\Requests\Consultor;

use Illuminate\Foundation\Http\FormRequest;

class OrcamentoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('orcamento'));
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:aprovando',
            'anotacoes' => 'nullable|string|max:3000',
            'anotacoes_tecnicas' => 'nullable|string|max:3000',
        ];
    }
}
