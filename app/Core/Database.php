<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * PADRÃO SINGLETON (Criacional)
 *
 * Problema: cada script do sistema legado abria sua própria conexão. Com o Neon,
 * cada conexão nova custa um handshake TLS e conta no limite de conexões do plano.
 * Solução: uma única instância de PDO por requisição, obtida com Database::getInstance().
 * Todos os repositórios compartilham essa conexão — por isso uma transação aberta no
 * VendaService vale também para o ProdutoRepository usado pelos observers.
 */
final class Database
{
    private static ?PDO $instancia = null;

    /** Construtor privado: ninguém faz "new Database()". */
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('Não é permitido desserializar o Singleton Database.');
    }

    public static function getInstance(): PDO
    {
        if (self::$instancia === null) {
            [$dsn, $usuario, $senha] = self::montarDsn((string) Config::get('db_url'), (string) Config::get('timezone'));

            self::$instancia = new PDO($dsn, $usuario, $senha, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instancia;
    }

    /**
     * Converte a URL do Neon (postgresql://user:senha@host/db?sslmode=require) em DSN do PDO.
     *
     * Lógica mantida do db.php legado: o endpoint ID (primeira parte do host, ex.: "ep-xxxx-pooler")
     * é enviado em options='endpoint=...'. Isso resolve o erro "Endpoint ID is not specified"
     * em hospedagens cuja libpq é antiga e não envia SNI. O fuso horário da sessão do
     * PostgreSQL também é definido aqui (--timezone=America/Cuiaba).
     *
     * @return array{0: string, 1: string, 2: string} [dsn, usuario, senha]
     */
    public static function montarDsn(string $url, string $timezone = 'America/Cuiaba'): array
    {
        if ($url === '') {
            throw new RuntimeException('Variável SRVDB não definida. Copie o .env.example para .env e preencha a conexão do Neon.');
        }

        $partes = parse_url($url);
        if ($partes === false || empty($partes['host'])) {
            throw new RuntimeException('SRVDB inválida. Formato esperado: postgresql://usuario:senha@host/banco');
        }

        $host     = $partes['host'];
        $porta    = $partes['port'] ?? 5432;
        $banco    = ltrim($partes['path'] ?? '/neondb', '/');
        $usuario  = urldecode($partes['user'] ?? '');
        $senha    = urldecode($partes['pass'] ?? '');
        $endpoint = explode('.', $host)[0];

        $query = [];
        parse_str($partes['query'] ?? '', $query);
        $sslmode = is_string($query['sslmode'] ?? null) ? $query['sslmode'] : 'require';

        $dsn = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;sslmode=%s;options='endpoint=%s --timezone=%s'",
            $host,
            (int) $porta,
            $banco,
            $sslmode,
            $endpoint,
            $timezone
        );

        return [$dsn, $usuario, $senha];
    }
}
