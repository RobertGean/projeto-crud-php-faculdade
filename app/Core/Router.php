<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Router usado pelo FRONT CONTROLLER (public/index.php).
 *
 * Mapeia "MÉTODO rota" => [Controller, método]. Um segmento numérico no fim da rota
 * (/clientes/editar/5) vira o parâmetro "id", então as duas formas de URL funcionam.
 * Toda requisição POST passa pela verificação de token CSRF antes de chegar ao controller.
 */
final class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $rotas = ['GET' => [], 'POST' => []];

    public function get(string $rota, array $acao): void
    {
        $this->rotas['GET'][trim($rota, '/')] = $acao;
    }

    public function post(string $rota, array $acao): void
    {
        $this->rotas['POST'][trim($rota, '/')] = $acao;
    }

    public function despachar(Request $request): void
    {
        $metodo = $request->metodo();
        $rota = $request->rota();

        // "/clientes/editar/5" => rota "clientes/editar" + id=5
        if (!$this->existe($rota) && preg_match('#^(.+)/(\d+)$#', $rota, $m)) {
            $rota = $m[1];
            $request->definirParametro('id', $m[2]);
        }

        $acao = $this->rotas[$metodo][$rota] ?? null;

        if ($acao === null) {
            if ($this->existe($rota)) {
                http_response_code(405);
                View::exibir('erros/404', ['titulo' => 'Método não permitido', 'mensagem' => 'Esta ação só pode ser executada pelo formulário correspondente.']);
                return;
            }
            http_response_code(404);
            View::exibir('erros/404', ['titulo' => 'Página não encontrada', 'mensagem' => 'A rota "' . $rota . '" não existe.']);
            return;
        }

        if ($metodo === 'POST' && !Csrf::validar($request->texto('_csrf'))) {
            Flash::erro('Sua sessão expirou ou o formulário é inválido (token CSRF). Tente novamente.');
            header('Location: ' . ($request->referer() ?: url()));
            return;
        }

        [$classe, $metodoController] = $acao;
        /** @var Controller $controller */
        $controller = new $classe();
        $controller->definirRequest($request);
        $controller->$metodoController();
    }

    private function existe(string $rota): bool
    {
        return isset($this->rotas['GET'][$rota]) || isset($this->rotas['POST'][$rota]);
    }
}
