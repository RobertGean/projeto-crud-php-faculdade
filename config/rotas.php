<?php

declare(strict_types=1);

/*
 * Tabela de rotas. Cada rota funciona nos dois formatos:
 *   index.php?rota=clientes/editar&id=5   e   /clientes/editar/5
 * Ações que alteram dados (salvar, excluir, finalizar...) são somente POST.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\CarrinhoController;
use App\Controllers\ClienteController;
use App\Controllers\HomeController;
use App\Controllers\PdvController;
use App\Controllers\ProdutoController;
use App\Controllers\RelatorioController;

// Início / Dashboard
$router->get('', [HomeController::class, 'index']);
$router->get('home', [HomeController::class, 'index']);

// Produtos
$router->get('produtos', [ProdutoController::class, 'index']);
$router->get('produtos/novo', [ProdutoController::class, 'novo']);
$router->post('produtos/salvar', [ProdutoController::class, 'salvar']);
$router->get('produtos/editar', [ProdutoController::class, 'editar']);
$router->post('produtos/atualizar', [ProdutoController::class, 'atualizar']);
$router->post('produtos/excluir', [ProdutoController::class, 'excluir']);
$router->post('produtos/inativar', [ProdutoController::class, 'inativar']);
$router->post('produtos/ativar', [ProdutoController::class, 'ativar']);

// 1 – Cadastro de clientes
$router->get('clientes', [ClienteController::class, 'index']);
$router->get('clientes/novo', [ClienteController::class, 'novo']);
$router->post('clientes/salvar', [ClienteController::class, 'salvar']);
$router->get('clientes/ver', [ClienteController::class, 'ver']);
$router->get('clientes/editar', [ClienteController::class, 'editar']);
$router->post('clientes/atualizar', [ClienteController::class, 'atualizar']);
$router->post('clientes/excluir', [ClienteController::class, 'excluir']);

// PDV (busca rápida de produtos — substitui o pdv.php legado)
$router->get('pdv', [PdvController::class, 'index']);
$router->post('pdv/adicionar', [PdvController::class, 'adicionar']);

// 2 – Carrinho de vendas
$router->get('carrinho', [CarrinhoController::class, 'index']);
$router->get('carrinho/novo', [CarrinhoController::class, 'novo']);
$router->post('carrinho/criar', [CarrinhoController::class, 'criar']);
$router->get('carrinho/ver', [CarrinhoController::class, 'ver']);
$router->post('carrinho/adicionar', [CarrinhoController::class, 'adicionar']);
$router->post('carrinho/atualizar-item', [CarrinhoController::class, 'atualizarItem']);
$router->post('carrinho/remover-item', [CarrinhoController::class, 'removerItem']);
$router->post('carrinho/cliente', [CarrinhoController::class, 'definirCliente']);
$router->post('carrinho/esvaziar', [CarrinhoController::class, 'esvaziar']);
$router->post('carrinho/cancelar', [CarrinhoController::class, 'cancelar']);
$router->post('carrinho/excluir', [CarrinhoController::class, 'excluir']);
$router->post('carrinho/finalizar', [CarrinhoController::class, 'finalizar']);

// 3 – Relatórios de vendas
$router->get('relatorios', [RelatorioController::class, 'index']);
$router->get('relatorios/venda', [RelatorioController::class, 'venda']);
$router->get('relatorios/editar', [RelatorioController::class, 'editar']);
$router->post('relatorios/atualizar', [RelatorioController::class, 'atualizar']);
$router->post('relatorios/cancelar', [RelatorioController::class, 'cancelar']);
$router->post('relatorios/excluir', [RelatorioController::class, 'excluir']);
$router->get('relatorios/exportar', [RelatorioController::class, 'exportar']);
