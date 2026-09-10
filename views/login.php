<?php
/** @var string $redirectTo */
?>
<div class="login-card panel">
    <div class="login-head">
        <span class="brand-mark">AX</span>
        <div>
            <h1>Entrar</h1>
            <p class="lede">Use o e-mail e a senha cadastrados para acessar o Assex.</p>
        </div>
    </div>

    <form method="post" action="login.php" autocomplete="on">
        <?= csrf_field() ?>
        <input type="hidden" name="redirect" value="<?= h($redirectTo) ?>">

        <div class="form-grid form-grid-1">
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" maxlength="191" required autocomplete="username">
            </div>

            <div class="field">
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </div>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">Entrar</button>
        </div>
    </form>
</div>
