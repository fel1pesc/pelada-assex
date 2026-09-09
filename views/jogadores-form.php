<div class="page-head">
    <div>
        <h1><?= $jogador['id'] !== '' ? 'Editar jogador' : 'Novo jogador' ?></h1>
        <p class="lede">Os números alimentam o ranking de gols e assistências.</p>
    </div>
    <a class="btn secondary" href="jogadores.php">Voltar ao elenco</a>
</div>

<form class="panel" method="post" action="jogadores.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= h((string) $jogador['id']) ?>">

    <div class="form-grid">
        <div class="field">
            <label for="nome">Nome</label>
            <input id="nome" name="nome" type="text" maxlength="80" required value="<?= h((string) $jogador['nome']) ?>">
        </div>

        <div class="field">
            <label for="gols">Gols</label>
            <input id="gols" name="gols" type="number" min="0" step="1" required value="<?= (int) $jogador['gols'] ?>">
        </div>

        <div class="field">
            <label for="assistencias">Assistências</label>
            <input id="assistencias" name="assistencias" type="number" min="0" step="1" required value="<?= (int) $jogador['assistencias'] ?>">
        </div>

        <div class="field">
            <label for="vitorias">Vitórias</label>
            <input id="vitorias" name="vitorias" type="number" min="0" step="1" required value="<?= (int) $jogador['vitorias'] ?>">
        </div>

        <div class="field full">
            <label class="check" for="mensalista">
                <input id="mensalista" name="mensalista" type="checkbox" value="1" <?= $jogador['mensalista'] ? 'checked' : '' ?>>
                Mensalista
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn" type="submit">Salvar</button>
        <a class="btn secondary" href="jogadores.php">Cancelar</a>
    </div>
</form>
