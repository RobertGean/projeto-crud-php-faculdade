<?php

declare(strict_types=1);

/*
 * Funções auxiliares usadas principalmente pelas Views.
 */

use App\Core\Config;
use App\Core\Csrf;
use App\Patterns\Strategy\FormaPagamentoFactory;

if (!function_exists('e')) {
    /** Escapa qualquer valor para saída HTML (proteção contra XSS). */
    function e(mixed $valor): string
    {
        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/** Caminho base da aplicação na URL (ex.: "" no php -S, "/public" com o .htaccess da raiz). */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim(dirname($script), '/.');
    }
    return $base;
}

/**
 * Gera o link de uma rota.
 * url('clientes/editar', ['id' => 5]) => index.php?rota=clientes/editar&id=5  (padrão, funciona em qualquer servidor)
 *                                      => /clientes/editar/5                    (com APP_URL_AMIGAVEL=true)
 */
function url(string $rota = '', array $params = []): string
{
    $rota = trim($rota, '/');

    if (Config::get('url_amigavel')) {
        if (isset($params['id']) && $rota !== '') {
            $rota .= '/' . (int) $params['id'];
            unset($params['id']);
        }
        $consulta = $params ? '?' . http_build_query($params) : '';
        return base_url() . '/' . $rota . $consulta;
    }

    if ($rota === '') {
        $consulta = $params ? '?' . http_build_query($params) : '';
        return base_url() . '/index.php' . $consulta;
    }

    return base_url() . '/index.php?' . http_build_query(['rota' => $rota] + $params);
}

/** URL de um arquivo dentro de public/. */
function asset(string $caminho): string
{
    return base_url() . '/' . ltrim($caminho, '/');
}

/** URL da imagem do produto, com imagem padrão quando não houver arquivo. */
function imagem_produto(?string $arquivo): string
{
    if ($arquivo) {
        $nome = basename($arquivo);
        if (is_file(Config::get('upload.diretorio') . '/' . $nome)) {
            return asset(Config::get('upload.url') . '/' . rawurlencode($nome));
        }
    }
    return asset('assets/img/sem-imagem.svg');
}

/** 1234.5 => "R$ 1.234,50" */
function moeda(mixed $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/** "2026-11-09" => "09/11/2026"; com hora => "09/11/2026 14:30". */
function data_br(?string $data, bool $comHora = false): string
{
    if ($data === null || $data === '') {
        return '—';
    }
    $timestamp = strtotime($data);
    if ($timestamp === false) {
        return e($data);
    }
    return date($comHora ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
}

/** "12345678909" => "123.456.789-09" */
function cpf_formatado(?string $cpf): string
{
    $digitos = preg_replace('/\D/', '', (string) $cpf);
    if (strlen($digitos) !== 11) {
        return (string) $cpf;
    }
    return substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-' . substr($digitos, 9, 2);
}

/** Campo oculto com o token CSRF — obrigatório em todo formulário POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? 'selected' : '';
}

function checked(mixed $valor): string
{
    return $valor ? 'checked' : '';
}

/** Rótulo amigável de uma forma de pagamento ("pix" => "PIX"). */
function rotulo_pagamento(?string $codigo): string
{
    return FormaPagamentoFactory::rotulos()[$codigo] ?? (string) $codigo;
}

/** Badge Bootstrap para status de venda/carrinho. */
function badge_status(string $status): string
{
    $cores = [
        'concluida'  => 'success',
        'aberto'     => 'primary',
        'finalizado' => 'success',
        'cancelada'  => 'danger',
        'cancelado'  => 'danger',
    ];
    $rotulos = ['concluida' => 'Concluída', 'cancelada' => 'Cancelada', 'aberto' => 'Aberto', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'];
    $cor = $cores[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $cor . '">' . e($rotulos[$status] ?? $status) . '</span>';
}

/** Mensagem de erro de validação de um campo (classe + texto) para os formulários. */
function classe_erro(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? ' is-invalid' : '';
}

function msg_erro(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? '<div class="invalid-feedback">' . e($erros[$campo]) . '</div>' : '';
}
