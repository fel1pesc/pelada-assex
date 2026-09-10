<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) ? $uri : '/';
$script = basename($uri);

if ($script === '' || $script === '/') {
    $script = 'index.php';
}

$allowed = [
    'index.php',
    'jogadores.php',
    'peladas.php',
    'usuarios.php',
    'login.php',
    'logout.php',
    'relatorio.php',
];

$root = dirname(__DIR__);
$path = $root . DIRECTORY_SEPARATOR . $script;

if (!in_array($script, $allowed, true) || !is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Página não encontrada.';
    exit;
}

$_SERVER['SCRIPT_NAME'] = '/' . $script;
$_SERVER['PHP_SELF'] = '/' . $script;
chdir($root);

try {
    require $path;
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><meta charset="utf-8"><pre>';
    echo htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '</pre>';
    exit;
}
