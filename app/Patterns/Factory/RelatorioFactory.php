<?php

declare(strict_types=1);

namespace App\Patterns\Factory;

use App\Core\RegraNegocioException;
use App\Repositories\Contracts\VendaRepositoryInterface;
use App\Repositories\VendaRepository;

/**
 * PADRÃO FACTORY METHOD (Criacional)
 *
 * Problema: a tela de relatórios e a exportação CSV precisam gerar relatórios diferentes
 * (por período, por cliente, por produto) a partir de um parâmetro "tipo" da URL.
 * Sem a fábrica, controller e exportação teriam o mesmo switch/case espalhado.
 * Solução: o método criar($tipo) é o único ponto que decide qual classe concreta instanciar;
 * quem usa só conhece a interface Relatorio. Novo relatório = nova classe + uma linha aqui.
 */
final class RelatorioFactory
{
    /** @var array<string, class-string<Relatorio>> */
    private const TIPOS = [
        'periodo' => RelatorioPorPeriodo::class,
        'cliente' => RelatorioPorCliente::class,
        'produto' => RelatorioPorProduto::class,
    ];

    public static function criar(string $tipo, ?VendaRepositoryInterface $vendas = null): Relatorio
    {
        $classe = self::TIPOS[$tipo] ?? throw new RegraNegocioException("Tipo de relatório inválido: {$tipo}");
        return new $classe($vendas ?? new VendaRepository());
    }

    /** @return array<string, string> tipo => título (para o seletor da tela) */
    public static function tipos(): array
    {
        return [
            'periodo' => 'Por período (lista de vendas)',
            'cliente' => 'Por cliente',
            'produto' => 'Por produto',
        ];
    }

    public static function existe(string $tipo): bool
    {
        return isset(self::TIPOS[$tipo]);
    }
}
