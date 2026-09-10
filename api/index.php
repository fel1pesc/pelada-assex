<?php

declare(strict_types=1);

if (($_GET['ping'] ?? '') === '1') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'pong php=' . PHP_VERSION . PHP_EOL;
    echo 'bootstrap=' . (is_file(dirname(__DIR__) . '/src/bootstrap.php') ? 'yes' : 'no') . PHP_EOL;
    echo 'mysql=' . (is_file(dirname(__DIR__) . '/src/Mysql.php') ? 'yes' : 'no') . PHP_EOL;
    echo 'ca=' . (is_file(__DIR__ . '/isrgrootx1.pem') ? 'yes' : 'no') . PHP_EOL;
    echo 'dbhost=' . (getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'unset')) . PHP_EOL;
    echo 'dbport=' . (getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? 'unset')) . PHP_EOL;
    echo 'dbname=' . (getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'unset')) . PHP_EOL;
    echo 'dbuser=' . (getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'unset')) . PHP_EOL;
    echo 'dbssl=' . (getenv('DB_SSL') ?: ($_ENV['DB_SSL'] ?? $_SERVER['DB_SSL'] ?? 'unset')) . PHP_EOL;
    echo 'vercel=' . (getenv('VERCEL') ?: 'unset') . PHP_EOL;
    echo 'pdo=' . (class_exists('PDO') ? 'yes' : 'no') . PHP_EOL;
    echo 'pdo_mysql=' . (in_array('mysql', PDO::getAvailableDrivers(), true) ? 'yes' : 'no') . PHP_EOL;
    exit;
}

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
