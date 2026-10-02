<?php

namespace App\Http\Controllers\Admin\Produtos;

class PaineisController extends ProdutosPorCategoriaController
{
    protected function slug(): string
    {
        return 'painel-solar';
    }

    protected function nomeCategoria(): string
    {
        return 'Painel Solar';
    }

    protected function pagina(): string
    {
        return 'Paineis';
    }

    protected function propRegistro(): string
    {
        return 'painel';
    }

    protected function mensagens(): array
    {
        return ['Painel solar criado com sucesso.', 'Painel solar atualizado com sucesso.', 'Painel solar removido.'];
    }
}
