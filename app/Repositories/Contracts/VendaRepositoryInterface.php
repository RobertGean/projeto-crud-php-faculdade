<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ItemVenda;
use App\Models\Venda;

/**
 * Filtros aceitos ($filtros): data_inicio, data_fim (AAAA-MM-DD), cliente_id, forma_pagamento, status.
 */
interface VendaRepositoryInterface
{
    public function inserir(Venda $venda): int;

    public function inserirItem(int $vendaId, ItemVenda $item): void;

    public function buscarPorId(int $id): ?Venda;

    /** Trava a venda (FOR UPDATE) e a retorna com os itens — usar dentro de transação. */
    public function bloquear(int $id): ?Venda;

    /** @return Venda[] */
    public function listar(array $filtros, int $limite, int $offset): array;

    public function contar(array $filtros): int;

    /** @return array{quantidade: int, concluidas: int, canceladas: int, faturamento: float, ticket_medio: float} */
    public function resumo(array $filtros): array;

    /** @return list<array{produto_id: int, cod: string, nome: string, quantidade: int, faturamento: float}> */
    public function maisVendidos(array $filtros, ?int $limite = 10): array;

    /** @return list<array{forma_pagamento: string, quantidade: int, total: float}> */
    public function porFormaPagamento(array $filtros): array;

    /** @return list<array{cliente_id: ?int, cliente_nome: ?string, cliente_cpf: ?string, quantidade: int, total: float}> */
    public function porCliente(array $filtros): array;

    /** @return Venda[] histórico de um cliente */
    public function listarPorCliente(int $clienteId): array;

    /** Atualiza forma de pagamento, valores e observação. */
    public function atualizarPagamento(Venda $venda): void;

    public function definirStatus(int $id, string $status, ?string $observacao): void;

    public function excluir(int $id): void;
}
