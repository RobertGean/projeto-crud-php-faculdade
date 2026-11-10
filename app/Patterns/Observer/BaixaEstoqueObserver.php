<?php

declare(strict_types=1);

namespace App\Patterns\Observer;

use App\Models\Venda;
use App\Repositories\Contracts\ProdutoRepositoryInterface;

/**
 * Observer que mantém o estoque coerente com as vendas:
 *  - venda finalizada => baixa a quantidade vendida;
 *  - venda cancelada  => devolve (estorna) a quantidade ao estoque.
 *
 * Roda DENTRO da transação aberta pelo VendaService (mesma conexão Singleton):
 * se qualquer baixa falhar, a venda inteira sofre rollback.
 */
final class BaixaEstoqueObserver implements VendaObserver
{
    public function __construct(private ProdutoRepositoryInterface $produtos)
    {
    }

    public function atualizar(string $evento, Venda $venda): void
    {
        foreach ($venda->itens as $item) {
            match ($evento) {
                self::FINALIZADA => $this->produtos->baixarEstoque($item->produtoId, $item->quantidade),
                self::CANCELADA  => $this->produtos->estornarEstoque($item->produtoId, $item->quantidade),
                default          => null,
            };
        }
    }
}
