<?php

declare(strict_types=1);

namespace App\Patterns\Factory;

/**
 * Produto do padrão Factory Method: todo relatório sabe seu título, suas colunas
 * e como gerar suas linhas. A tela e a exportação CSV tratam todos da mesma forma.
 */
interface Relatorio
{
    public function titulo(): string;

    /**
     * @return array<string, array{0: string, 1: string}> chave => [rótulo, tipo]
     *         tipos: texto, inteiro, moeda, data, cpf, pagamento, status
     */
    public function colunas(): array;

    /** @return list<array<string, mixed>> */
    public function linhas(array $filtros): array;
}
