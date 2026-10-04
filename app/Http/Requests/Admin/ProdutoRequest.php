<?php

namespace App\Http\Requests\Admin;

use App\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Regras de produto do catálogo (painéis, inversores, transformadores e demais categorias).
 */
class ProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // acesso já restrito pelo middleware "admin"
    }

    public function rules(): array
    {
        $atual = $this->route('catalogo');

        return [
            'categoria_id' => 'required|exists:categorias_produtos,id',
            'marca_id' => 'nullable|exists:marcas,id',
            'fornecedor_id' => 'nullable|exists:fornecedores,id',
            'nome' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'sku' => ['nullable', 'string', 'max:60', Rule::unique('produtos', 'sku')->ignore($atual instanceof Produto ? $atual->id : null)],
            'descricao' => 'nullable|string',
            'potencia' => 'nullable|numeric|min:0',
            'unidade_potencia' => 'nullable|string|max:10',
            'tensao' => 'nullable|integer|min:0',
            // Obrigatório: sem custo o produto fica a R$ 0 e a trava de venda abaixo do custo não protege nada.
            'preco_custo' => 'required|numeric|min:0',
            'garantia' => 'nullable|string|max:100',
            'ativo' => 'boolean',
            'unidade' => 'nullable|string|max:20',
            'imagem_url' => 'nullable|url|max:500',
            'ficha_tecnica_url' => 'nullable|url|max:500',
            'atributos' => 'nullable|array',
        ];
    }
}
