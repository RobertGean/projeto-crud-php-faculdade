<?php

declare(strict_types=1);

namespace App\Patterns\Strategy;

/** Cartão de débito: sem desconto nem acréscimo. */
final class PagamentoCartaoDebito extends PagamentoBase
{
    public function codigo(): string
    {
        return 'debito';
    }

    public function nome(): string
    {
        return 'Cartão de débito';
    }

    public function percentualAjuste(): float
    {
        return 0.0;
    }
}
