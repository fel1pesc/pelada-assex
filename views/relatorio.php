<?php
/** @var list<array{id: string, nome: string, mensalista: bool, gols: int, assistencias: int, vitorias: int}> $rankingGols */
/** @var list<array{id: string, nome: string, mensalista: bool, gols: int, assistencias: int, vitorias: int}> $rankingVitorias */
/** @var string $filtro */

$filtros = [
    'todos' => 'Todos',
    'sim' => 'Mensalista: sim',
    'nao' => 'Mensalista: não',
];

$queryFiltro = h($filtro);
?>
<div class="page-head">
    <div>
        <h1>Relatório</h1>
        <p class="lede">Ranking de gols e vitórias do elenco.</p>
    </div>
    <div class="actions">
        <a class="btn" href="relatorio.php?export=gols&amp;mensalista=<?= $queryFiltro ?>" target="_blank" rel="noopener">
            Exportar PDF de gols
        </a>
        <a class="btn secondary" href="relatorio.php?export=vitorias&amp;mensalista=<?= $queryFiltro ?>" target="_blank" rel="noopener">
            Exibir PDF de vitórias
        </a>
    </div>
</div>

<div class="filters">
    <?php foreach ($filtros as $valor => $rotulo): ?>
        <a class="<?= $filtro === $valor ? 'is-active' : '' ?>" href="relatorio.php?mensalista=<?= h($valor) ?>">
            <?= h($rotulo) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="rank-grid">
    <section class="panel">
        <h2>Gols</h2>
        <?php if ($rankingGols === []): ?>
            <div class="empty">Nenhum jogador neste filtro.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Jogador</th>
                        <th>Mensalista</th>
                        <th class="num">Gols</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankingGols as $posicao => $item): ?>
                        <?php
                        $lugar = $posicao + 1;
                        $classe = match ($lugar) {
                            1 => 'gold',
                            2 => 'silver',
                            3 => 'bronze',
                            default => '',
                        };
                        ?>
                        <tr>
                            <td><span class="rank <?= $classe ?>"><?= $lugar ?></span></td>
                            <td><?= h($item['nome']) ?></td>
                            <td>
                                <span class="badge <?= $item['mensalista'] ? 'badge-yes' : 'badge-no' ?>">
                                    <?= $item['mensalista'] ? 'Sim' : 'Não' ?>
                                </span>
                            </td>
                            <td class="num"><span class="stat"><?= (int) $item['gols'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Vitórias</h2>
        <?php if ($rankingVitorias === []): ?>
            <div class="empty">Nenhum jogador neste filtro.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Jogador</th>
                        <th>Mensalista</th>
                        <th class="num">Vitórias</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankingVitorias as $posicao => $item): ?>
                        <?php
                        $lugar = $posicao + 1;
                        $classe = match ($lugar) {
                            1 => 'gold',
                            2 => 'silver',
                            3 => 'bronze',
                            default => '',
                        };
                        ?>
                        <tr>
                            <td><span class="rank <?= $classe ?>"><?= $lugar ?></span></td>
                            <td><?= h($item['nome']) ?></td>
                            <td>
                                <span class="badge <?= $item['mensalista'] ? 'badge-yes' : 'badge-no' ?>">
                                    <?= $item['mensalista'] ? 'Sim' : 'Não' ?>
                                </span>
                            </td>
                            <td class="num"><span class="stat"><?= (int) $item['vitorias'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
