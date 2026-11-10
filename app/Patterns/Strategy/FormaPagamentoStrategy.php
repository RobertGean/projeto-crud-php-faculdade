<?php

declare(strict_types=1);

namespace App\Patterns\Strategy;

/**
 * PADRÃO STRATEGY (Comportamental)
 *
 * Problema: cada forma de pagamento tem uma regra diferente (desconto no dinheiro e no PIX,
 * taxa no crédito, troco só no dinheiro). Com if/else no VendaService, toda forma nova
 * exigiria mexer na regra de venda.
 * Solução: cada forma é uma classe que implementa esta interface; o VendaService só chama
 * calcular() sem saber qual estratégia concreta está usando.
 */
interface FormaPagamentoStrategy
{
    /** Código gravado no banco (vendas.forma_pagamento). */
    public function codigo(): string;

    /** Nome exibido nas telas. */
    public function nome(): string;

    /** Percentual de ajuste: negativo = desconto, positivo = acréscimo. Usado também na prévia da tela. */
    public function percentualAjuste(): float;

    /** Se a forma aceita valor recebido maior que o total (gera troco). */
    public function aceitaTroco(): bool;

    /**
     * @param float      $subtotal      soma dos itens
     * @param float|null $valorRecebido informado pelo operador (obrigatório quando aceitaTroco())
     * @throws \App\Core\RegraNegocioException se o valor recebido for insuficiente
     */
    public function calcular(float $subtotal, ?float $valorRecebido = null): ResultadoPagamento;
}
