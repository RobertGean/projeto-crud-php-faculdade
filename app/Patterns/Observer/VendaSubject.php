<?php

declare(strict_types=1);

namespace App\Patterns\Observer;

use App\Models\Venda;

/**
 * PADRÃO OBSERVER — interface do "Subject" (quem é observado). Implementada pelo VendaService.
 */
interface VendaSubject
{
    public function anexar(VendaObserver $observer): void;

    public function desanexar(VendaObserver $observer): void;

    public function notificar(string $evento, Venda $venda): void;
}
