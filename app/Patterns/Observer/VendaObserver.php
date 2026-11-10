<?php

declare(strict_types=1);

namespace App\Patterns\Observer;

use App\Models\Venda;

/**
 * PADRÃO OBSERVER (Comportamental) — interface dos observadores.
 *
 * Problema: ao finalizar ou cancelar uma venda, várias coisas precisam acontecer
 * (baixar/estornar estoque, registrar log...). Colocar tudo dentro do VendaService
 * o deixaria acoplado a cada uma dessas tarefas.
 * Solução: o VendaService (Subject) apenas avisa "venda.finalizada"/"venda.cancelada";
 * cada observador decide o que fazer. Para adicionar, por exemplo, envio de e-mail,
 * basta criar um novo observer e anexá-lo — sem alterar o VendaService.
 */
interface VendaObserver
{
    public const FINALIZADA = 'venda.finalizada';
    public const CANCELADA = 'venda.cancelada';

    public function atualizar(string $evento, Venda $venda): void;
}
