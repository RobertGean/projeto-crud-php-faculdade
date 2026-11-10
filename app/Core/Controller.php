<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Controller base. Os controllers recebem a requisição, validam a entrada,
 * chamam Services/Repositories e escolhem a View. Não contêm SQL nem HTML.
 */
abstract class Controller
{
    protected Request $request;

    public function definirRequest(Request $request): void
    {
        $this->request = $request;
    }

    protected function render(string $view, array $dados = [], int $status = 200): void
    {
        http_response_code($status);
        View::exibir($view, $dados);
    }

    protected function redirecionar(string $rota = '', array $params = []): never
    {
        header('Location: ' . url($rota, $params));
        exit;
    }

    protected function naoEncontrado(string $mensagem = 'Registro não encontrado.'): never
    {
        $this->render('erros/404', ['titulo' => 'Não encontrado', 'mensagem' => $mensagem], 404);
        exit;
    }

    /** ID da rota (?id=5 ou /rota/5). Retorna 0 se ausente/inválido. */
    protected function id(): int
    {
        return max(0, $this->request->inteiro('id'));
    }

    protected function pagina(): int
    {
        return max(1, $this->request->inteiro('pagina', 1));
    }
}
