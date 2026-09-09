<?php
/** @var array{id: string, data: string, observacao: string, partidas: list<array<string, mixed>>} $pelada */
/** @var array<string, array{id: string, nome: string}> $playersById */
/** @var array{gols: list<array{id: string, nome: string, gols: int, vitorias: int}>, vitorias: list<array{id: string, nome: string, gols: int, vitorias: int}>} $rankingPelada */

function format_pelada_date(string $data): string
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $data);

    return $date ? $date->format('d/m/Y') : $data;
}

function player_name(array $playersById, string $id): string
{
    return $playersById[$id]['nome'] ?? 'Jogador removido';
}

/**
 * @param list<array{id: string, nome: string, gols: int, vitorias: int}> $ranking
 * @param 'gols'|'vitorias' $stat
 */
function render_pelada_ranking(string $titulo, array $ranking, string $stat): void
{
    ?>
    <section class="panel">
        <h2><?= h($titulo) ?></h2>
        <?php if ($ranking === []): ?>
            <div class="empty">Nenhum jogador neste ranking.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Jogador</th>
                        <th class="num"><?= h($titulo) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking as $posicao => $item): ?>
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
                            <td class="num"><span class="stat"><?= (int) $item[$stat] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php
}
?>
<div class="page-head">
    <div>
        <h1>Pelada · <?= h(format_pelada_date($pelada['data'])) ?></h1>
        <p class="lede">Atualize a data, a observação e gerencie as partidas do dia.</p>
    </div>
    <a class="btn secondary" href="peladas.php">Voltar</a>
</div>

<form class="panel" method="post" action="peladas.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= h($pelada['id']) ?>">

    <div class="form-grid form-grid-date">
        <div class="field">
            <label for="data">Data</label>
            <input id="data" name="data" type="date" required value="<?= h($pelada['data']) ?>">
        </div>

        <div class="field full">
            <label for="observacao">Observação</label>
            <textarea id="observacao" name="observacao" maxlength="500" rows="3" placeholder="Opcional"><?= h((string) ($pelada['observacao'] ?? '')) ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn" type="submit">Salvar</button>
    </div>
</form>

<div class="page-head section-head">
    <div>
        <h2>Partidas</h2>
        <p class="lede"><?= count($pelada['partidas']) ?> partida<?= count($pelada['partidas']) === 1 ? '' : 's' ?> nesta pelada.</p>
    </div>
    <a class="btn" href="peladas.php?acao=partida&id=<?= h($pelada['id']) ?>">Nova partida</a>
</div>

<div class="panel">
    <?php if ($pelada['partidas'] === []): ?>
        <div class="empty">Nenhuma partida ainda. Cadastre a primeira com os dois times.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Placar</th>
                    <th>Vencedor</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pelada['partidas'] as $index => $item): ?>
                    <?php
                    $vencedor = match ($item['vencedor']) {
                        'a' => 'Time A',
                        'b' => 'Time B',
                        default => 'Empate',
                    };
                    ?>
                    <tr>
                        <td>Partida <?= $index + 1 ?></td>
                        <td>
                            <span class="scoreline">
                                <strong><?= (int) $item['placar_a'] ?></strong>
                                <span>×</span>
                                <strong><?= (int) $item['placar_b'] ?></strong>
                            </span>
                        </td>
                        <td>
                            <?php if ($item['vencedor'] === 'empate'): ?>
                                <span class="badge badge-no"><?= h($vencedor) ?></span>
                            <?php else: ?>
                                <span class="badge badge-yes"><?= h($vencedor) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="peladas.php?acao=partida&id=<?= h($pelada['id']) ?>&partida=<?= h($item['id']) ?>">Editar</a>
                                <form method="post" action="peladas.php" onsubmit="return confirm('Excluir esta partida e reverter gols/vitórias?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="acao" value="excluir_partida">
                                    <input type="hidden" name="pelada_id" value="<?= h($pelada['id']) ?>">
                                    <input type="hidden" name="partida_id" value="<?= h($item['id']) ?>">
                                    <button class="btn danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr class="match-summary">
                        <td colspan="4">
                            <div class="match-teams">
                                <div>
                                    <strong>Time A</strong>
                                    <span>
                                        <?php
                                        $nomesA = [];
                                        foreach ($item['time_a']['jogadores'] as $playerId) {
                                            $nomesA[] = player_name($playersById, (string) $playerId);
                                        }
                                        if (!empty($item['time_a']['goleiro'])) {
                                            $nomesA[] = player_name($playersById, (string) $item['time_a']['goleiro']) . ' (G)';
                                        }
                                        echo h(implode(', ', $nomesA));
                                        ?>
                                    </span>
                                </div>
                                <div>
                                    <strong>Time B</strong>
                                    <span>
                                        <?php
                                        $nomesB = [];
                                        foreach ($item['time_b']['jogadores'] as $playerId) {
                                            $nomesB[] = player_name($playersById, (string) $playerId);
                                        }
                                        if (!empty($item['time_b']['goleiro'])) {
                                            $nomesB[] = player_name($playersById, (string) $item['time_b']['goleiro']) . ' (G)';
                                        }
                                        echo h(implode(', ', $nomesB));
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <?php if (($item['observacao'] ?? '') !== ''): ?>
                                <p class="note"><?= h((string) $item['observacao']) ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if ($pelada['partidas'] !== []): ?>
    <div class="page-head section-head">
        <div>
            <h2>Ranking da pelada</h2>
            <p class="lede">Gols e vitórias do dia, sem jogadores avulsos.</p>
        </div>
    </div>

    <div class="rank-grid rank-grid-compact">
        <?php
        render_pelada_ranking('Gols', $rankingPelada['gols'], 'gols');
        render_pelada_ranking('Vitórias', $rankingPelada['vitorias'], 'vitorias');
        ?>
    </div>
<?php endif; ?>
