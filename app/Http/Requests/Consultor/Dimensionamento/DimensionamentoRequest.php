<?php

namespace App\Http\Requests\Consultor\Dimensionamento;

use App\Models\Kit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Base dos fluxos de orçamento (grupos tarifários e dimensionamento legado).
 *
 * A mesma classe valida as duas etapas:
 *  - cálculo / busca de kits: regras do fluxo + filtro de categorias;
 *  - salvar (rotas *.store): regras do fluxo + kit escolhido + anotações.
 */
abstract class DimensionamentoRequest extends FormRequest
{
    /** Regras específicas do fluxo (tensão, consumo, tarifas...). */
    abstract protected function regrasDoFluxo(): array;

    public function authorize(): bool
    {
        return true; // acesso já restrito pelo middleware "consultor"
    }

    public function salvando(): bool
    {
        return str_ends_with((string) $this->route()?->getName(), '.store');
    }

    public function rules(): array
    {
        $comuns = [
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('consultor_id', $this->user()->id)],
            'estrutura_id' => 'required|exists:estruturas,id',
            'orientacao' => 'required|in:norte,nordeste_noroeste,leste_oeste,sudeste_sudoeste,sul',
        ];

        $etapa = $this->salvando()
            ? [
                // Mesmo filtro da busca de kits: kit desativado ou indisponível no fornecedor não é orçado.
                'kit_id' => ['required', Rule::exists('kits', 'id')->where('ativo', true)->where('ativo_fornecedor', true)],
                'anotacoes' => 'nullable|string|max:3000',
                'anotacoes_tecnicas' => 'nullable|string|max:3000',
            ]
            : [
                'categorias' => 'nullable|array',
                'categorias.*' => Rule::in(array_keys(Kit::CATEGORIAS)),
            ];

        return $comuns + $this->regrasDoFluxo() + $etapa;
    }
}
