<div class="page-head">
    <div>
        <h1>Nova pelada</h1>
        <p class="lede">Informe a data e, se quiser, uma observação. As partidas são cadastradas na edição.</p>
    </div>
    <a class="btn secondary" href="peladas.php">Voltar</a>
</div>

<form class="panel" method="post" action="peladas.php">
    <?= csrf_field() ?>

    <div class="form-grid form-grid-date">
        <div class="field">
            <label for="data">Data</label>
            <input id="data" name="data" type="date" required value="<?= h((string) $pelada['data']) ?>">
        </div>

        <div class="field full">
            <label for="observacao">Observação</label>
            <textarea id="observacao" name="observacao" maxlength="500" rows="3" placeholder="Opcional"><?= h((string) ($pelada['observacao'] ?? '')) ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn" type="submit">Salvar</button>
        <a class="btn secondary" href="peladas.php">Cancelar</a>
    </div>
</form>
