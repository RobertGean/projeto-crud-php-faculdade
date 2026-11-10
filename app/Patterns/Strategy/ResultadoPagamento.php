<?php

declare(strict_types=1);

namespace App\Patterns\Strategy;

/**
 * Objeto de valor com o resultado do cálculo de uma forma de pagamento.
 */
final class ResultadoPagamento
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $desconto,
        public readonly float $acrescimo,
        public readonly float $total,
        public readonly float $valorRecebido,
        public readonly float $troco
    ) {
    }
}
