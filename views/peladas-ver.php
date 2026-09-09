<?php
/** @var array{id: string, data: string, observacao: string, partidas: list<array<string, mixed>>} $pelada */
/** @var array<string, array{id: string, nome: string}> $playersById */

function format_pelada_date(string $data): string
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $data);

    return $date ? $date->format('d/m/Y') : $data;
}

function player_name(array $playersById, string $id): string
{
    $nome = trim((string) ($playersById[$id]['nome'] ?? ''));

    return $nome !== '' ? $nome : 'Jogador removido';
}

/**
 * @param array{jogadores: list<string>, goleiro: ?string, gols: array<string, int>} $team
 * @param array<string, array{id: string, nome: string}> $playersById
 * @return list<array{id: string, nome: string, gols: int, goleiro: bool}>
 */
function match_squad(array $team, array $playersById): array
{
    $squad = [];

    foreach ($team['jogadores'] as $playerId) {
        $id = (string) $playerId;

        if ($id === '') {
            continue;
        }

        $squad[] = [
            'id' => $id,
            'nome' => player_name($playersById, $id),
            'gols' => (int) ($team['gols'][$id] ?? 0),
            'goleiro' => false,
        ];
    }

    $goleiro = (string) ($team['goleiro'] ?? '');

    if ($goleiro !== '') {
        $squad[] = [
            'id' => $goleiro,
            'nome' => player_name($playersById, $goleiro),
            'gols' => (int) ($team['gols'][$goleiro] ?? 0),
            'goleiro' => true,
        ];
    }

    return $squad;
}

/**
 * @param list<array{id: string, nome: string, gols: int, goleiro: bool}> $squad
 * @return list<array{id: string, nome: string, gols: int, goleiro: bool}>
 */
function match_scorers(array $squad): array
{
    $scorers = array_values(array_filter(
        $squad,
        static fn (array $player): bool => $player['gols'] > 0
    ));

    usort($scorers, static function (array $a, array $b): int {
        $diff = $b['gols'] <=> $a['gols'];

        return $diff !== 0 ? $diff : strcasecmp($a['nome'], $b['nome']);
    });

    return $scorers;
}

/**
 * @param list<array{id: string, nome: string, gols: int, goleiro: bool}> $squad
 */
function render_match_squad(string $label, array $squad, bool $winner): void
{
    ?>
    <section class="match-squad<?= $winner ? ' is-winner' : '' ?>">
        <h3><?= h($label) ?></h3>
        <?php if ($squad === []): ?>
            <p class="note">Nenhum jogador neste time.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($squad as $player): ?>
                    <li>
                        <span>
                            <?= h($player['nome']) ?>
                            <?php if ($player['goleiro']): ?>
                                <small>Goleiro</small>
                            <?php endif; ?>
                        </span>
                        <?php if ($player['gols'] > 0): ?>
                            <span class="goal-count"><?= (int) $player['gols'] ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * @param list<array{id: string, nome: string, gols: int, goleiro: bool}> $scorers
 */
function render_scorers(string $label, array $scorers): void
{
    ?>
    <div>
        <strong><?= h($label) ?></strong>
        <?php if ($scorers === []): ?>
            <p class="note">Sem gols</p>
        <?php else: ?>
            <ul class="scorer-list">
                <?php foreach ($scorers as $player): ?>
                    <li>
                        <span><?= h($player['nome']) ?></span>
                        <span class="goal-count"><?= (int) $player['gols'] ?> gol<?= $player['gols'] === 1 ? '' : 's' ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
}

$isAdmin = is_admin();
?>
<div class="page-head">
    <div>
        <h1>Pelada · <?= h(format_pelada_date($pelada['data'])) ?></h1>
        <p class="lede">
            <?= count($pelada['partidas']) ?> partida<?= count($pelada['partidas']) === 1 ? '' : 's' ?>
            nesta pelada.
        </p>
        <?php if (($pelada['observacao'] ?? '') !== ''): ?>
            <p class="note"><?= h((string) $pelada['observacao']) ?></p>
        <?php endif; ?>
    </div>
    <div class="actions">
        <?php if ($isAdmin): ?>
            <a class="btn" href="peladas.php?acao=editar&id=<?= h($pelada['id']) ?>">Editar</a>
        <?php endif; ?>
        <a class="btn secondary" href="peladas.php">Voltar</a>
    </div>
</div>

<?php if ($pelada['partidas'] === []): ?>
    <div class="panel">
        <div class="empty">Nenhuma partida registrada nesta pelada.</div>
    </div>
<?php else: ?>
    <div class="match-view-list">
        <?php foreach ($pelada['partidas'] as $index => $item): ?>
            <?php
            $squadA = match_squad($item['time_a'], $playersById);
            $squadB = match_squad($item['time_b'], $playersById);
            $scorersA = match_scorers($squadA);
            $scorersB = match_scorers($squadB);
            $vencedor = (string) $item['vencedor'];
            $vencedorLabel = match ($vencedor) {
                'a' => 'Time A venceu',
                'b' => 'Time B venceu',
                default => 'Empate',
            };
            ?>
            <article class="match-view">
                <header class="match-view-head">
                    <h2>Partida <?= $index + 1 ?></h2>
                    <?php if ($vencedor === 'empate'): ?>
                        <span class="badge badge-no"><?= h($vencedorLabel) ?></span>
                    <?php else: ?>
                        <span class="badge badge-yes"><?= h($vencedorLabel) ?></span>
                    <?php endif; ?>
                </header>

                <div class="match-view-scoreboard">
                    <div class="match-view-side<?= $vencedor === 'a' ? ' is-winner' : '' ?>">
                        <span class="muted-label">Time A</span>
                        <strong><?= (int) $item['placar_a'] ?></strong>
                    </div>
                    <span class="match-view-vs">×</span>
                    <div class="match-view-side<?= $vencedor === 'b' ? ' is-winner' : '' ?>">
                        <span class="muted-label">Time B</span>
                        <strong><?= (int) $item['placar_b'] ?></strong>
                    </div>
                </div>

                <div class="match-view-body">
                    <?php
                    render_match_squad('Time A', $squadA, $vencedor === 'a');
                    render_match_squad('Time B', $squadB, $vencedor === 'b');
                    ?>
                </div>



                <?php if (($item['observacao'] ?? '') !== ''): ?>
                    <p class="note"><?= h((string) $item['observacao']) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
