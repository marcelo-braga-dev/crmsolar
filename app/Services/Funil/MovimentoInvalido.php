<?php

namespace App\Services\Funil;

use RuntimeException;

/** Movimentação recusada por regra de negócio do funil — a mensagem vai direto para o usuário. */
class MovimentoInvalido extends RuntimeException {}
