<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Log em arquivo (storage/logs). Erros técnicos (PDO etc.) vão para cá e o usuário
 * vê apenas uma mensagem amigável.
 */
final class Logger
{
    public static function erro(string $mensagem, array $contexto = []): void
    {
        self::escrever('app.log', 'ERRO', $mensagem, $contexto);
    }

    public static function info(string $mensagem, array $contexto = []): void
    {
        self::escrever('app.log', 'INFO', $mensagem, $contexto);
    }

    public static function escrever(string $arquivo, string $nivel, string $mensagem, array $contexto = []): void
    {
        $diretorio = (string) Config::get('logs', BASE_PATH . '/storage/logs');
        if (!is_dir($diretorio)) {
            @mkdir($diretorio, 0775, true);
        }

        $linha = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            $nivel,
            $mensagem,
            $contexto ? ' ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );

        // Se não for possível gravar (permissão), cai no error_log do PHP em vez de quebrar a página.
        if (@file_put_contents($diretorio . '/' . basename($arquivo), $linha, FILE_APPEND | LOCK_EX) === false) {
            error_log(trim($linha));
        }
    }
}
