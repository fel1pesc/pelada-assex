<?php
/** @var list<array{id: string, data: string, observacao: string, partidas: list<array<string, mixed>>}> $lista */

function format_pelada_date(string $data): string
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $data);

    return $date ? $date->format('d/m/Y') : $data;
}
$isAdmin = is_admin();
?>
<div class="page-head">
    <div>
        <h1>Peladas</h1>
        <p class="lede"><?= count($lista) ?> pelada<?= count($lista) === 1 ? '' : 's' ?> cadastrada<?= count($lista) === 1 ? '' : 's' ?>.</p>
    </div>
    <?php if ($isAdmin): ?>
        <a class="btn" href="peladas.php?acao=novo">Nova pelada</a>
    <?php endif; ?>
</div>

<div class="panel">
    <?php if ($lista === []): ?>
        <div class="empty">Nenhuma pelada ainda. Cadastre a primeira para registrar as partidas.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th class="num">Partidas</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $item): ?>
                    <tr>
                        <td>
                            <?= h(format_pelada_date($item['data'])) ?>
                            <?php if (($item['observacao'] ?? '') !== ''): ?>
                                <?php $preview = preg_replace('/\s+/u', ' ', (string) $item['observacao']) ?? ''; ?>
                                <div class="note"><?= h(mb_strimwidth($preview, 0, 80, '…')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= count($item['partidas']) ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn" href="peladas.php?acao=ver&id=<?= h($item['id']) ?>">Ver</a>
                                <?php if ($isAdmin): ?>
                                    <a class="btn secondary" href="peladas.php?acao=editar&id=<?= h($item['id']) ?>">Abrir</a>
                                    <form method="post" action="peladas.php" onsubmit="return confirm('Excluir esta pelada e reverter gols/vitórias das partidas?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= h($item['id']) ?>">
                                        <button class="btn danger" type="submit">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
