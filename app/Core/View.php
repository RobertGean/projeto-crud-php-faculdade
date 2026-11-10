<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Renderiza as Views (arquivos PHP em app/Views) dentro do layout principal.
 * A View só apresenta dados: não acessa banco, não lê $_POST.
 */
final class View
{
    public static function exibir(string $view, array $dados = [], ?string $layout = 'layouts/main'): void
    {
        echo self::renderizar($view, $dados, $layout);
    }

    public static function renderizar(string $view, array $dados = [], ?string $layout = 'layouts/main'): string
    {
        $conteudo = self::incluir($view, $dados);

        if ($layout === null) {
            return $conteudo;
        }

        return self::incluir($layout, $dados + ['conteudo' => $conteudo, 'flashes' => Flash::consumir()]);
    }

    /** Renderiza um partial (usado dentro das views): <?= View::partial('partials/paginacao', [...]) ?> */
    public static function partial(string $view, array $dados = []): string
    {
        return self::incluir($view, $dados);
    }

    private static function incluir(string $view, array $dados): string
    {
        $arquivo = APP_PATH . '/Views/' . $view . '.php';
        if (!is_file($arquivo)) {
            throw new RuntimeException("View não encontrada: {$view}");
        }

        // Função isolada: a view só enxerga as variáveis de $dados.
        $renderizar = static function (string $__arquivo, array $__dados): void {
            extract($__dados, EXTR_SKIP);
            require $__arquivo;
        };

        ob_start();
        try {
            $renderizar($arquivo, $dados);
        } catch (\Throwable $erro) {
            ob_end_clean();
            throw $erro;
        }
        return (string) ob_get_clean();
    }
}
