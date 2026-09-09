<?php
/** @var list<array{id: string, nome: string, mensalista: bool, gols: int, assistencias: int, vitorias: int}> $elenco */
/** @var string $filtro */

$filtros = [
    'todos' => 'Todos',
    'sim' => 'Mensalista: sim',
    'nao' => 'Mensalista: não',
];
$isAdmin = is_admin();
?>
<div class="page-head">
    <div>
        <h1>Elenco</h1>
        <p class="lede"><?= count($elenco) ?> jogador<?= count($elenco) === 1 ? '' : 'es' ?> cadastrado<?= count($elenco) === 1 ? '' : 's' ?>.</p>
    </div>
    <?php if ($isAdmin): ?>
        <a class="btn" href="jogadores.php?acao=novo">Novo jogador</a>
    <?php endif; ?>
</div>

<div class="filters">
    <?php foreach ($filtros as $valor => $rotulo): ?>
        <a class="<?= $filtro === $valor ? 'is-active' : '' ?>" href="jogadores.php?mensalista=<?= h($valor) ?>">
            <?= h($rotulo) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="panel">
    <?php if ($elenco === []): ?>
        <div class="empty"><?= $filtro === 'todos' ? 'Nenhum jogador ainda. Cadastre o primeiro para montar o ranking.' : 'Nenhum jogador neste filtro.' ?></div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Mensalista</th>
                    <th class="num">Gols</th>
                    <th class="num">Vitórias</th>
                    <?php if ($isAdmin): ?>
                        <th></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($elenco as $item): ?>
                    <tr>
                        <td><?= h($item['nome']) ?></td>
                        <td>
                            <?php if ($item['mensalista']): ?>
                                <span class="badge badge-yes">Sim</span>
                            <?php else: ?>
                                <span class="badge badge-no">Não</span>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= (int) $item['gols'] ?></td>
                        <td class="num"><?= (int) $item['vitorias'] ?></td>
                        <?php if ($isAdmin): ?>
                            <td>
                                <div class="actions">
                                    <a class="btn secondary" href="jogadores.php?acao=editar&id=<?= h($item['id']) ?>">Editar</a>
                                    <form method="post" action="jogadores.php" onsubmit="return confirm('Excluir este jogador?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= h($item['id']) ?>">
                                        <button class="btn danger" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
