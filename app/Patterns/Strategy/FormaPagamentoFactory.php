<?php

declare(strict_types=1);

namespace App\Patterns\Strategy;

use App\Core\RegraNegocioException;

/**
 * Escolhe a estratégia de pagamento a partir do código vindo do formulário.
 * É o único lugar que conhece todas as estratégias concretas.
 */
final class FormaPagamentoFactory
{
    /** @var array<string, class-string<FormaPagamentoStrategy>> */
    private const ESTRATEGIAS = [
        'dinheiro' => PagamentoDinheiro::class,
        'pix'      => PagamentoPix::class,
        'debito'   => PagamentoCartaoDebito::class,
        'credito'  => PagamentoCartaoCredito::class,
    ];

    public static function criar(string $codigo): FormaPagamentoStrategy
    {
        $classe = self::ESTRATEGIAS[$codigo] ?? null;
        if ($classe === null) {
            throw new RegraNegocioException('Forma de pagamento inválida.');
        }
        return new $classe();
    }

    /** @return FormaPagamentoStrategy[] */
    public static function todas(): array
    {
        return array_map(static fn (string $classe) => new $classe(), array_values(self::ESTRATEGIAS));
    }

    /** @return array<string, string> codigo => nome */
    public static function rotulos(): array
    {
        $rotulos = [];
        foreach (self::todas() as $estrategia) {
            $rotulos[$estrategia->codigo()] = $estrategia->nome();
        }
        return $rotulos;
    }
}
