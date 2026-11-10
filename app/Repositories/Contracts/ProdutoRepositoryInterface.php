<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Produto;

interface ProdutoRepositoryInterface
{
    /** @return Produto[] Busca por nome OU código (corrige o bug do campo "codigo" do legado). */
    public function buscar(string $termo, int $limite, int $offset, bool $somenteAtivos = false): array;

    public function contar(string $termo, bool $somenteAtivos = false): int;

    public function buscarPorId(int $id): ?Produto;

    public function buscarPorCodigo(string $cod): ?Produto;

    /** Lê a quantidade travando a linha (SELECT ... FOR UPDATE) — usar dentro de transação. */
    public function estoqueComBloqueio(int $id): ?int;

    public function existeCodigo(string $cod, ?int $ignorarId = null): bool;

    public function inserir(Produto $produto): int;

    public function atualizar(Produto $produto): void;

    public function excluir(int $id): void;

    public function definirAtivo(int $id, bool $ativo): void;

    public function possuiVendas(int $id): bool;

    /** Diminui o estoque; falha se o resultado ficaria negativo. */
    public function baixarEstoque(int $id, int $quantidade): void;

    public function estornarEstoque(int $id, int $quantidade): void;

    /** @return Produto[] */
    public function estoqueBaixo(int $limite): array;

    /** @return Produto[] produtos vencidos ou que vencem nos próximos $dias */
    public function vencendo(int $dias): array;
}
