<?php
/** @var array{id: int, nome: string, email: string, admin: bool} $usuario */

$editando = $usuario['id'] > 0;
?>
<div class="page-head">
    <div>
        <h1><?= $editando ? 'Editar usuário' : 'Novo usuário' ?></h1>
        <p class="lede">
            <?= $editando
                ? 'Nome e e-mail são obrigatórios. Deixe a senha em branco para manter a atual.'
                : 'Nome, e-mail e senha são obrigatórios. A senha é gravada com o hash padrão do PHP.'
            ?>
        </p>
    </div>
    <a class="btn secondary" href="usuarios.php">Voltar aos usuários</a>
</div>

<form class="panel" method="post" action="usuarios.php" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">

    <div class="form-grid form-grid-2">
        <div class="field">
            <label for="nome">Nome</label>
            <input id="nome" name="nome" type="text" maxlength="80" required value="<?= h((string) $usuario['nome']) ?>">
        </div>

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" maxlength="191" required value="<?= h((string) $usuario['email']) ?>">
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input
                id="password"
                name="password"
                type="password"
                minlength="6"
                autocomplete="new-password"
                <?= $editando ? '' : 'required' ?>
            >
            <?php if ($editando): ?>
                <span class="hint">Deixe em branco para manter a senha atual.</span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label class="check" for="admin">
                <input id="admin" name="admin" type="checkbox" value="1" <?= $usuario['admin'] ? 'checked' : '' ?>>
                Administrador
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn" type="submit">Salvar</button>
        <a class="btn secondary" href="usuarios.php">Cancelar</a>
    </div>
</form>
