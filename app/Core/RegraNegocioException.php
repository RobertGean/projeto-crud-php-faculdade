<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Erro de regra de negócio (estoque insuficiente, carrinho fechado, CPF duplicado...).
 * A mensagem é segura para mostrar ao usuário, ao contrário de uma PDOException.
 */
class RegraNegocioException extends RuntimeException
{
}
