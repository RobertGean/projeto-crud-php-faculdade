<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Acesso somente leitura às configurações carregadas de config/config.php.
 * Aceita chaves com ponto: Config::get('upload.tamanho_max').
 */
final class Config
{
    private static array $valores = [];

    public static function carregar(array $valores): void
    {
        self::$valores = $valores;
    }

    public static function get(string $chave, mixed $padrao = null): mixed
    {
        $atual = self::$valores;
        foreach (explode('.', $chave) as $parte) {
            if (!is_array($atual) || !array_key_exists($parte, $atual)) {
                return $padrao;
            }
            $atual = $atual[$parte];
        }
        return $atual;
    }
}
