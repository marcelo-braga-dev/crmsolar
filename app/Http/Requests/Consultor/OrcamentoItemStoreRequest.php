<?php

namespace App\Http\Requests\Consultor;

use App\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrcamentoItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('orcamento'));
    }

    protected function prepareForValidation(): void
    {
        // O formulário envia tipo "produto" mesmo sem produto do catálogo selecionado.
        if ($this->input('tipo') === 'produto' && ! $this->filled('produto_id')) {
            $this->merge(['tipo' => 'personalizado', 'produto_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'tipo' => 'required|in:produto,servico,personalizado',
            'produto_id' => [
                'nullable', 'required_if:tipo,produto',
                Rule::exists('produtos', 'id')->where('ativo', true)->where('ativo_fornecedor', true),
            ],
            'descricao' => 'nullable|required_without:produto_id|string|max:255',
            'quantidade' => 'required|integer|min:1',
            'preco_venda_unitario' => 'required|numeric|min:0',
        ];
    }

    /** @return array<int, \Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('produto_id')) {
                    return;
                }

                $custo = (float) Produto::whereKey($this->input('produto_id'))->value('preco_custo');

                if ((float) $this->input('preco_venda_unitario') < $custo) {
                    $validator->errors()->add(
                        'preco_venda_unitario',
                        'Preço de venda não pode ser menor que o custo do produto (R$ '.number_format($custo, 2, ',', '.').').',
                    );
                }
            },
        ];
    }
}
