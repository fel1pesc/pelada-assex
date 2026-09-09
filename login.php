<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

if (current_user() !== null) {
    redirect('jogadores.php');
}

$redirectTo = safe_internal_path((string) ($_GET['redirect'] ?? $_POST['redirect'] ?? 'jogadores.php'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $user = $users->authenticate(
        (string) ($_POST['email'] ?? ''),
        (string) ($_POST['password'] ?? '')
    );

    if ($user === null) {
        flash('error', 'E-mail ou senha inválidos.');
        redirect('login.php?redirect=' . urlencode($redirectTo));
    }

    auth_login($user);
    redirect($redirectTo);
}

$title = 'Entrar';
$loginPage = true;
ob_start();
require __DIR__ . '/views/login.php';
$content = ob_get_clean();
require __DIR__ . '/views/layout.php';
