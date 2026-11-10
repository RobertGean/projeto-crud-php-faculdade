<?php
/**
 * Botão que envia um POST (com CSRF) após confirmação no navegador.
 * A confirmação real acontece no servidor: as rotas de exclusão só aceitam POST com token válido.
 *
 * @var string $acao     rota (ex.: 'clientes/excluir')
 * @var int    $id
 * @var string $rotulo
 * @var string $classe   classes do botão
 * @var string $mensagem texto da confirmação
 */
$classe = $classe ?? 'btn btn-sm btn-outline-danger';
$mensagem = $mensagem ?? 'Tem certeza?';
$icone = $icone ?? '';
?>
<form method="post" action="<?= e(url($acao)) ?>" class="d-inline" data-confirmar="<?= e($mensagem) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <button type="submit" class="<?= e($classe) ?>"><?php if ($icone): ?><i class="bi <?= e($icone) ?>"></i> <?php endif; ?><?= e($rotulo) ?></button>
</form>
