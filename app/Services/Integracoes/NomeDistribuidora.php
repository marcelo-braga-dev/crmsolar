<?php

namespace App\Services\Integracoes;

use App\Services\Demo\ModoDemonstracao;

/**
 * Nome da distribuidora integrada como aparece nas telas. Na demonstração o nome real é
 * confidencial: telas, menu, URL e dados da base usam só "Distribuidora".
 */
class NomeDistribuidora
{
    public const REAL = 'Edeltec';

    public const GENERICO = 'Distribuidora';

    /** Tipo do histórico de integrações como sai para o navegador (o banco guarda "edeltec"). */
    public const TIPO_PUBLICO = 'distribuidora';

    public function __construct(private readonly ModoDemonstracao $demo) {}

    public function exibido(): string
    {
        return $this->demo->ativo() ? self::GENERICO : self::REAL;
    }
}
