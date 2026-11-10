<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Cliente;

interface ClienteRepositoryInterface
{
    /** @return Cliente[] busca por nome ou CPF */
    public function buscar(string $termo, int $limite, int $offset): array;

    public function contar(string $termo): int;

    public function buscarPorId(int $id): ?Cliente;

    public function existeCpf(string $cpf, ?int $ignorarId = null): bool;

    public function inserir(Cliente $cliente): int;

    public function atualizar(Cliente $cliente): void;

    public function excluir(int $id): void;

    public function definirAtivo(int $id, bool $ativo): void;

    public function possuiVendas(int $id): bool;

    /** @return Cliente[] clientes ativos para combos (carrinho, filtros) */
    public function listarAtivos(): array;

    /** @return Cliente[] todos (inclusive inativos), para filtros de relatório */
    public function listarTodos(): array;
}
