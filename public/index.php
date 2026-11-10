<?php

declare(strict_types=1);

/*
 * FRONT CONTROLLER — ponto único de entrada da aplicação.
 *
 * Toda requisição passa por aqui: inicializa o ambiente, abre a sessão,
 * carrega a tabela de rotas e entrega a requisição ao Router.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

Session::iniciar();

$router = new Router();
require BASE_PATH . '/config/rotas.php';

$router->despachar(Request::capturar());
