<?php

namespace App\Http\Controllers\Admin\Produtos;

class InversoresController extends ProdutosPorCategoriaController
{
    protected function slug(): string
    {
        return 'inversor-solar';
    }

    protected function nomeCategoria(): string
    {
        return 'Inversor Solar';
    }

    protected function pagina(): string
    {
        return 'Inversores';
    }

    protected function propRegistro(): string
    {
        return 'inversor';
    }

    protected function mensagens(): array
    {
        return ['Inversor criado com sucesso.', 'Inversor atualizado com sucesso.', 'Inversor removido.'];
    }
}
