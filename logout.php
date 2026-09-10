<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(current_user() === null ? 'login.php' : 'jogadores.php');
}

csrf_verify();
auth_logout();
flash('success', 'Você saiu do sistema.');
redirect('login.php');
