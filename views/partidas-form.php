<?php
/** @var array{id: string, data: string, observacao?: string} $pelada */
/** @var array{id: string, time_a: array, time_b: array, placar_a: int, placar_b: int, vencedor: string, observacao?: string} $partida */
/** @var list<array{id: string, nome: string, mensalista: bool}> $elenco */

$date = DateTimeImmutable::createFromFormat('Y-m-d', $pelada['data']);
$dataLabel = $date ? $date->format('d/m/Y') : $pelada['data'];

/**
 * @param 'a'|'b' $side
 * @param array{jogadores: list<string>, goleiro: ?string, gols: array<string, int>} $team
 * @param list<array{id: string, nome: string, mensalista: bool}> $elenco
 */
function render_team_form(string $side, array $team, array $elenco): void
{
    $prefix = 'time_' . $side;
    $label = $side === 'a' ? 'Time A' : 'Time B';
    ?>
    <section class="team-card">
        <header class="team-card-head">
            <h2><?= h($label) ?></h2>
            <p class="lede">5 jogadores obrigatórios · goleiro opcional · diarista vira cadastro avulso</p>
        </header>

        <div class="team-slots">
            <?php for ($i = 0; $i < 5; $i++): ?>
                <?php
                $selected = (string) ($team['jogadores'][$i] ?? '');
                $goals = (int) ($team['gols'][$selected] ?? 0);
                ?>
                <div class="slot" data-slot="<?= $prefix ?>-<?= $i ?>">
                    <div class="slot-main">
                        <div class="slot-top">
                            <label for="<?= $prefix ?>_jogador_<?= $i ?>">Jogador <?= $i + 1 ?></label>
                            <label class="check check-inline">
                                <input
                                    type="checkbox"
                                    class="diarista-toggle"
                                    data-slot="<?= $prefix ?>-<?= $i ?>"
                                >
                                Diarista
                            </label>
                        </div>
                        <input type="hidden" class="modo-input" name="<?= $prefix ?>[modos][<?= $i ?>]" value="cadastro">
                        <select
                            id="<?= $prefix ?>_jogador_<?= $i ?>"
                            name="<?= $prefix ?>[jogadores][<?= $i ?>]"
                            class="player-select"
                            required
                            data-goals-input="<?= $prefix ?>_gols_<?= $i ?>"
                        >
                            <option value="">Selecione</option>
                            <?php foreach ($elenco as $jogador): ?>
                                <option value="<?= h($jogador['id']) ?>" <?= $selected === $jogador['id'] ? 'selected' : '' ?>>
                                    <?= h($jogador['nome']) ?><?= $jogador['mensalista'] ? '' : ' (diarista)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input
                            type="text"
                            class="diarista-name"
                            name="<?= $prefix ?>[diaristas][<?= $i ?>]"
                            maxlength="80"
                            placeholder="Nome do diarista"
                            disabled
                            hidden
                        >
                    </div>
                    <div class="field field-goals">
                        <label for="<?= $prefix ?>_gols_<?= $i ?>">Gols</label>
                        <input
                            id="<?= $prefix ?>_gols_<?= $i ?>"
                            class="goal-input"
                            type="number"
                            min="0"
                            step="1"
                            name="<?= $prefix ?>[gols_linha][<?= $i ?>]"
                            value="<?= $goals ?>"
                            <?= $selected === '' ? 'disabled' : '' ?>
                        >
                    </div>
                </div>
            <?php endfor; ?>

            <?php
            $goleiro = (string) ($team['goleiro'] ?? '');
            $goleiroGoals = (int) ($team['gols'][$goleiro] ?? 0);
            ?>
            <div class="slot slot-gk" data-slot="<?= $prefix ?>-gk">
                <div class="slot-main">
                    <div class="slot-top">
                        <label for="<?= $prefix ?>_goleiro">Goleiro</label>
                        <label class="check check-inline">
                            <input
                                type="checkbox"
                                class="diarista-toggle"
                                data-slot="<?= $prefix ?>-gk"
                            >
                            Diarista
                        </label>
                    </div>
                    <input type="hidden" class="modo-input" name="<?= $prefix ?>[goleiro_modo]" value="cadastro">
                    <select
                        id="<?= $prefix ?>_goleiro"
                        name="<?= $prefix ?>[goleiro]"
                        class="player-select"
                        data-goals-input="<?= $prefix ?>_gols_gk"
                    >
                        <option value="">Sem goleiro</option>
                        <?php foreach ($elenco as $jogador): ?>
                            <option value="<?= h($jogador['id']) ?>" <?= $goleiro === $jogador['id'] ? 'selected' : '' ?>>
                                <?= h($jogador['nome']) ?><?= $jogador['mensalista'] ? '' : ' (diarista)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input
                        type="text"
                        class="diarista-name"
                        name="<?= $prefix ?>[goleiro_diarista]"
                        maxlength="80"
                        placeholder="Nome do goleiro diarista"
                        disabled
                        hidden
                    >
                </div>
                <div class="field field-goals">
                    <label for="<?= $prefix ?>_gols_gk">Gols</label>
                    <input
                        id="<?= $prefix ?>_gols_gk"
                        class="goal-input"
                        type="number"
                        min="0"
                        step="1"
                        name="<?= $prefix ?>[gols_goleiro]"
                        value="<?= $goleiroGoals ?>"
                        <?= $goleiro === '' ? 'disabled' : '' ?>
                    >
                </div>
            </div>
        </div>
    </section>
    <?php
}
?>
<div class="page-head">
    <div>
        <h1><?= $partida['id'] !== '' ? 'Editar partida' : 'Nova partida' ?></h1>
        <p class="lede">Pelada de <?= h($dataLabel) ?>. Em empate, você escolhe o vencedor ou mantém o empate.</p>
    </div>
    <a class="btn secondary" href="peladas.php?acao=editar&id=<?= h($pelada['id']) ?>">Voltar à pelada</a>
</div>

<form class="match-form" method="post" action="peladas.php" id="match-form">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="salvar_partida">
    <input type="hidden" name="pelada_id" value="<?= h($pelada['id']) ?>">
    <input type="hidden" name="partida_id" value="<?= h((string) $partida['id']) ?>">
    <input type="hidden" name="vencedor" id="vencedor-input" value="<?= h((string) $partida['vencedor']) ?>">

    <div class="match-grid">
        <?php render_team_form('a', $partida['time_a'], $elenco); ?>
        <?php render_team_form('b', $partida['time_b'], $elenco); ?>
    </div>

    <div class="panel match-result">
        <div>
            <span class="muted-label">Placar previsto</span>
            <p class="scoreline scoreline-lg">
                <strong id="score-a"><?= (int) $partida['placar_a'] ?></strong>
                <span>×</span>
                <strong id="score-b"><?= (int) $partida['placar_b'] ?></strong>
            </p>
        </div>
        <div>
            <span class="muted-label">Vencedor</span>
            <p class="winner-label" id="winner-label">
                <?php
                echo match ($partida['vencedor']) {
                    'a' => 'Time A',
                    'b' => 'Time B',
                    default => 'Empate',
                };
                ?>
            </p>
        </div>
    </div>

    <div class="panel match-note">
        <div class="field">
            <label for="observacao">Observação</label>
            <textarea id="observacao" name="observacao" maxlength="500" rows="3" placeholder="Opcional"><?= h((string) ($partida['observacao'] ?? '')) ?></textarea>
        </div>
    </div>

    <div class="panel draw-panel" id="draw-panel" hidden>
        <h2>Empate no placar</h2>
        <p class="lede">Escolha um time vencedor ou mantenha o empate para continuar.</p>
        <div class="draw-actions">
            <button type="button" class="btn" data-winner="a">Time A venceu</button>
            <button type="button" class="btn" data-winner="b">Time B venceu</button>
            <button type="button" class="btn secondary" data-winner="empate">Manter empate</button>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn" type="submit" id="save-match">Salvar partida</button>
        <a class="btn secondary" href="peladas.php?acao=editar&id=<?= h($pelada['id']) ?>">Cancelar</a>
    </div>
</form>

<script>
(() => {
    const form = document.getElementById('match-form');
    if (!form || typeof window.jQuery === 'undefined') return;

    const $ = window.jQuery;
    const drawPanel = document.getElementById('draw-panel');
    const vencedorInput = document.getElementById('vencedor-input');
    const winnerLabel = document.getElementById('winner-label');
    let drawResolved = false;

    const winnerText = (value) => {
        if (value === 'a') return 'Time A';
        if (value === 'b') return 'Time B';
        return 'Empate';
    };

    const slotParts = (slot) => {
        const select = slot.querySelector('.player-select');
        const nameInput = slot.querySelector('.diarista-name');
        const modoInput = slot.querySelector('.modo-input');
        const toggle = slot.querySelector('.diarista-toggle');
        const goalsInput = document.getElementById(select?.dataset.goalsInput || '');
        return { select, nameInput, modoInput, toggle, goalsInput };
    };

    const destroySelect2 = (select) => {
        const $select = $(select);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
    };

    const initSelect2 = (select) => {
        const $select = $(select);
        destroySelect2(select);
        $select.select2({
            width: '100%',
            language: 'pt-BR',
            placeholder: select.id.includes('_goleiro') ? 'Sem goleiro' : 'Selecione',
            allowClear: select.id.includes('_goleiro'),
            dropdownParent: $(form),
        });
    };

    const syncGoalsEnabled = (slot) => {
        const { select, nameInput, toggle, goalsInput } = slotParts(slot);
        if (!goalsInput) return;

        const diarista = Boolean(toggle?.checked);
        const hasPlayer = diarista ? Boolean(nameInput?.value.trim()) : Boolean(select?.value);
        goalsInput.disabled = !hasPlayer;
    };

    const setDiaristaMode = (slot, enabled) => {
        const { select, nameInput, modoInput, toggle } = slotParts(slot);
        if (!select || !nameInput || !modoInput || !toggle) return;

        toggle.checked = enabled;
        modoInput.value = enabled ? 'diarista' : 'cadastro';

        if (enabled) {
            destroySelect2(select);
            select.hidden = true;
            select.disabled = true;
            select.removeAttribute('required');
            select.value = '';
            nameInput.hidden = false;
            nameInput.disabled = false;
            if (!select.id.includes('_goleiro')) {
                nameInput.required = true;
            }
        } else {
            nameInput.hidden = true;
            nameInput.disabled = true;
            nameInput.required = false;
            nameInput.value = '';
            select.hidden = false;
            select.disabled = false;
            if (!select.id.includes('_goleiro')) {
                select.required = true;
            }
            initSelect2(select);
        }

        syncGoalsEnabled(slot);
        refreshOptions();
        refreshScore();
    };

    const selectedIds = () =>
        [...form.querySelectorAll('.player-select')]
            .filter((select) => !select.disabled)
            .map((select) => select.value)
            .filter(Boolean);

    const refreshOptions = () => {
        const selected = selectedIds();

        form.querySelectorAll('.player-select').forEach((select) => {
            if (select.disabled) return;
            const current = select.value;
            [...select.options].forEach((option) => {
                if (!option.value) {
                    option.disabled = false;
                    return;
                }
                option.disabled = selected.includes(option.value) && option.value !== current;
            });

            if ($(select).hasClass('select2-hidden-accessible')) {
                $(select).trigger('change.select2');
            }
        });
    };

    const currentScores = () => {
        const sumSide = (side) =>
            [...form.querySelectorAll(`[id^="time_${side}_gols_"]`)]
                .filter((input) => !input.disabled)
                .reduce((total, input) => total + (parseInt(input.value || '0', 10) || 0), 0);

        return { a: sumSide('a'), b: sumSide('b') };
    };

    const refreshScore = () => {
        const { a, b } = currentScores();
        document.getElementById('score-a').textContent = String(a);
        document.getElementById('score-b').textContent = String(b);

        if (a > b) {
            drawResolved = false;
            drawPanel.hidden = true;
            vencedorInput.value = 'a';
            winnerLabel.textContent = 'Time A';
            return;
        }

        if (b > a) {
            drawResolved = false;
            drawPanel.hidden = true;
            vencedorInput.value = 'b';
            winnerLabel.textContent = 'Time B';
            return;
        }

        if (!drawResolved) {
            vencedorInput.value = 'empate';
            winnerLabel.textContent = 'Empate';
        } else {
            winnerLabel.textContent = winnerText(vencedorInput.value);
        }
    };

    form.querySelectorAll('.slot').forEach((slot) => {
        const { select, nameInput, toggle, goalsInput } = slotParts(slot);

        if (select && !select.disabled) {
            initSelect2(select);
        }

        toggle?.addEventListener('change', () => {
            setDiaristaMode(slot, toggle.checked);
        });

        $(select).on('change', () => {
            syncGoalsEnabled(slot);
            refreshOptions();
            refreshScore();
        });

        nameInput?.addEventListener('input', () => {
            syncGoalsEnabled(slot);
            refreshScore();
        });

        goalsInput?.addEventListener('input', () => {
            drawResolved = false;
            drawPanel.hidden = true;
            refreshScore();
        });

        syncGoalsEnabled(slot);
    });

    drawPanel.querySelectorAll('[data-winner]').forEach((button) => {
        button.addEventListener('click', () => {
            const winner = button.getAttribute('data-winner') || 'empate';
            vencedorInput.value = winner;
            winnerLabel.textContent = winnerText(winner);
            drawResolved = true;
            form.requestSubmit();
        });
    });

    form.addEventListener('submit', (event) => {
        const { a, b } = currentScores();

        if (a !== b || drawResolved) {
            return;
        }

        event.preventDefault();
        window.alert('A partida empatou no placar. Escolha um time vencedor ou mantenha o empate.');
        drawPanel.hidden = false;
        drawPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    refreshOptions();
    refreshScore();
})();
</script>
