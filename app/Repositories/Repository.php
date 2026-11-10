<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Base dos repositórios PostgreSQL. A conexão vem do Singleton Database e só é
 * aberta no primeiro acesso (páginas que não usam o banco não conectam no Neon).
 */
abstract class Repository
{
    public function __construct(private ?PDO $conexao = null)
    {
    }

    protected function db(): PDO
    {
        return $this->conexao ??= Database::getInstance();
    }

    /** Escapa % e _ para usar o termo digitado pelo usuário dentro de um ILIKE. */
    protected function termoLike(string $termo): string
    {
        return '%' . addcslashes($termo, '%_\\') . '%';
    }
}
