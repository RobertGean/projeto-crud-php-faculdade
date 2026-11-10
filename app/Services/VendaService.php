<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\RegraNegocioException;
use App\Models\Carrinho;
use App\Models\ItemVenda;
use App\Models\Venda;
use App\Patterns\Observer\BaixaEstoqueObserver;
use App\Patterns\Observer\LogVendaObserver;
use App\Patterns\Observer\VendaObserver;
use App\Patterns\Observer\VendaSubject;
use App\Patterns\Strategy\FormaPagamentoFactory;
use App\Repositories\CarrinhoRepository;
use App\Repositories\Contracts\CarrinhoRepositoryInterface;
use App\Repositories\Contracts\ProdutoRepositoryInterface;
use App\Repositories\Contracts\VendaRepositoryInterface;
use App\Repositories\ProdutoRepository;
use App\Repositories\VendaRepository;
use PDO;
use Throwable;

/**
 * SERVICE LAYER + SUBJECT do padrão Observer.
 *
 * Encapsula as operações de venda que envolvem várias tabelas. Cada uma roda numa
 * transação (beginTransaction/commit/rollBack): ou tudo é gravado, ou nada é.
 */
final class VendaService implements VendaSubject
{
    /** @var VendaObserver[] */
    private array $observers = [];

    public function __construct(
        private CarrinhoRepositoryInterface $carrinhos,
        private VendaRepositoryInterface $vendas,
        private ProdutoRepositoryInterface $produtos,
        private ?PDO $conexao = null
    ) {
    }

    /** Monta o serviço com os observadores padrão do sistema. */
    public static function padrao(): self
    {
        $produtos = new ProdutoRepository();
        $servico = new self(new CarrinhoRepository(), new VendaRepository(), $produtos);
        $servico->anexar(new BaixaEstoqueObserver($produtos));
        $servico->anexar(new LogVendaObserver());
        return $servico;
    }

    // ---------------------------------------------------------------- Observer

    public function anexar(VendaObserver $observer): void
    {
        $this->observers[spl_object_id($observer)] = $observer;
    }

    public function desanexar(VendaObserver $observer): void
    {
        unset($this->observers[spl_object_id($observer)]);
    }

    public function notificar(string $evento, Venda $venda): void
    {
        foreach ($this->observers as $observer) {
            $observer->atualizar($evento, $venda);
        }
    }

    // ---------------------------------------------------------------- Casos de uso

    /**
     * Finaliza o carrinho gerando uma venda.
     *  1. trava o carrinho e confere se ainda está aberto;
     *  2. trava cada produto (SELECT ... FOR UPDATE) e confere o estoque — duas vendas
     *     simultâneas do mesmo produto não conseguem deixar o estoque negativo;
     *  3. calcula desconto/acréscimo/troco com a Strategy da forma de pagamento;
     *  4. grava vendas + venda_itens e notifica os observers (baixa de estoque e log);
     *  5. marca o carrinho como finalizado e faz commit.
     */
    public function finalizar(int $carrinhoId, string $formaPagamento, ?float $valorRecebido, ?string $observacao = null): Venda
    {
        $estrategia = FormaPagamentoFactory::criar($formaPagamento);

        return $this->emTransacao(function () use ($carrinhoId, $estrategia, $valorRecebido, $observacao): Venda {
            $status = $this->carrinhos->bloquear($carrinhoId);
            if ($status === null) {
                throw new RegraNegocioException('Carrinho não encontrado.');
            }
            if ($status !== Carrinho::ABERTO) {
                throw new RegraNegocioException("Este carrinho já está {$status}.");
            }

            $carrinho = $this->carrinhos->buscarPorId($carrinhoId);
            if ($carrinho === null || $carrinho->vazio()) {
                throw new RegraNegocioException('Adicione pelo menos um produto antes de finalizar.');
            }

            foreach ($carrinho->itens as $item) {
                $estoque = $this->produtos->estoqueComBloqueio($item->produtoId) ?? 0;
                if ($item->quantidade > $estoque) {
                    throw new RegraNegocioException("Estoque insuficiente para \"{$item->produtoNome}\": disponível {$estoque}, no carrinho {$item->quantidade}.");
                }
            }

            $pagamento = $estrategia->calcular($carrinho->total(), $valorRecebido);

            $venda = new Venda(
                carrinhoId: $carrinho->id,
                clienteId: $carrinho->clienteId,
                clienteNome: $carrinho->clienteNome,
                formaPagamento: $estrategia->codigo(),
                subtotal: $pagamento->subtotal,
                desconto: $pagamento->desconto,
                acrescimo: $pagamento->acrescimo,
                total: $pagamento->total,
                valorRecebido: $pagamento->valorRecebido,
                troco: $pagamento->troco,
                observacao: $observacao !== '' ? $observacao : null,
                itens: array_map([ItemVenda::class, 'deItemCarrinho'], $carrinho->itens),
            );

            $venda->id = $this->vendas->inserir($venda);
            foreach ($venda->itens as $item) {
                $item->vendaId = $venda->id;
                $this->vendas->inserirItem($venda->id, $item);
            }

            $this->notificar(VendaObserver::FINALIZADA, $venda);
            $this->carrinhos->definirStatus($carrinho->id, Carrinho::FINALIZADO);

            return $venda;
        });
    }

    /** Cancela a venda e devolve os itens ao estoque (via BaixaEstoqueObserver). */
    public function cancelar(int $vendaId, string $motivo = ''): Venda
    {
        return $this->emTransacao(function () use ($vendaId, $motivo): Venda {
            $venda = $this->vendas->bloquear($vendaId) ?? throw new RegraNegocioException('Venda não encontrada.');
            if (!$venda->estaConcluida()) {
                throw new RegraNegocioException("A venda #{$vendaId} já está cancelada.");
            }

            $observacao = trim(($venda->observacao ?? '') . "\nCancelada em " . date('d/m/Y H:i') . ($motivo !== '' ? ": {$motivo}" : '.'));
            $this->vendas->definirStatus($venda->id, Venda::CANCELADA, $observacao);
            $venda->status = Venda::CANCELADA;
            $venda->observacao = $observacao;

            $this->notificar(VendaObserver::CANCELADA, $venda);
            return $venda;
        });
    }

    /**
     * Atualiza forma de pagamento e observação, recalculando os valores com a nova Strategy.
     * Para dinheiro, mantém o valor recebido informado (ou o total, se não informado).
     */
    public function atualizar(int $vendaId, string $formaPagamento, ?float $valorRecebido, ?string $observacao): Venda
    {
        $estrategia = FormaPagamentoFactory::criar($formaPagamento);

        return $this->emTransacao(function () use ($vendaId, $estrategia, $valorRecebido, $observacao): Venda {
            $venda = $this->vendas->bloquear($vendaId) ?? throw new RegraNegocioException('Venda não encontrada.');
            if (!$venda->estaConcluida()) {
                throw new RegraNegocioException('Vendas canceladas não podem ser editadas.');
            }

            if ($estrategia->aceitaTroco() && $valorRecebido === null) {
                $valorRecebido = $estrategia->calcular($venda->subtotal, PHP_FLOAT_MAX)->total;
            }
            $pagamento = $estrategia->calcular($venda->subtotal, $valorRecebido);

            $venda->formaPagamento = $estrategia->codigo();
            $venda->desconto = $pagamento->desconto;
            $venda->acrescimo = $pagamento->acrescimo;
            $venda->total = $pagamento->total;
            $venda->valorRecebido = $pagamento->valorRecebido;
            $venda->troco = $pagamento->troco;
            $venda->observacao = ($observacao ?? '') !== '' ? $observacao : null;

            $this->vendas->atualizarPagamento($venda);
            return $venda;
        });
    }

    /** Exclusão definitiva: somente de vendas já canceladas (o estoque já foi estornado). */
    public function excluir(int $vendaId): void
    {
        $this->emTransacao(function () use ($vendaId): void {
            $venda = $this->vendas->bloquear($vendaId) ?? throw new RegraNegocioException('Venda não encontrada.');
            if ($venda->estaConcluida()) {
                throw new RegraNegocioException('Somente vendas canceladas podem ser excluídas. Cancele a venda primeiro.');
            }
            $this->vendas->excluir($venda->id);
        });
    }

    /**
     * @template T
     * @param callable(): T $operacao
     * @return T
     */
    private function emTransacao(callable $operacao): mixed
    {
        $pdo = $this->conexao ??= Database::getInstance();
        $pdo->beginTransaction();
        try {
            $resultado = $operacao();
            $pdo->commit();
            return $resultado;
        } catch (Throwable $erro) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $erro;
        }
    }
}
