<?php

namespace App\Http\Controllers\Admin\Produtos;

class TrafosController extends ProdutosPorCategoriaController
{
    protected function slug(): string
    {
        return 'transformador';
    }

    protected function nomeCategoria(): string
    {
        return 'Transformador';
    }

    protected function pagina(): string
    {
        return 'Trafos';
    }

    protected function propRegistro(): string
    {
        return 'trafo';
    }

    protected function mensagens(): array
    {
        return ['Transformador criado com sucesso.', 'Transformador atualizado com sucesso.', 'Transformador removido.'];
    }
}
