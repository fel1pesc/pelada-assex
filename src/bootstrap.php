<?php

declare(strict_types=1);

require_once __DIR__ . '/Mysql.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/DbSessionHandler.php';
require_once __DIR__ . '/PlayerRepository.php';
require_once __DIR__ . '/PeladaRepository.php';
require_once __DIR__ . '/UserRepository.php';

const APP_NAME = 'Assex';

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_set_save_handler(new DbSessionHandler(), true);
session_start();

$database = new Database();
$players = new PlayerRepository($database);
$peladas = new PeladaRepository($database);
$users = new UserRepository(Mysql::pdo());

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['_csrf'] ?? '';

    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sessão expirada. Recarregue a página e tente de novo.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);

    return is_array($flash) ? $flash : null;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function current_page(): string
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');

    return match ($script) {
        'relatorio.php' => 'relatorio',
        'peladas.php' => 'peladas',
        'usuarios.php' => 'usuarios',
        'login.php' => 'login',
        default => 'jogadores',
    };
}

function current_user(): ?array
{
    static $cached = false;

    if ($cached !== false) {
        return $cached;
    }

    $id = (int) ($_SESSION['user_id'] ?? 0);

    if ($id <= 0) {
        $cached = null;
        return null;
    }

    global $users;
    $found = $users->find($id);

    if ($found === null) {
        unset($_SESSION['user_id']);
        $cached = null;
        return null;
    }

    $cached = $found;

    return $cached;
}

function auth_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
}

function auth_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool) ($params['secure'] ?? false),
            (bool) ($params['httponly'] ?? true)
        );
    }

    session_destroy();
    session_start();
}

function is_public_page(): bool
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');

    return in_array($script, ['login.php', 'logout.php'], true);
}

function safe_internal_path(string $path): string
{
    $path = trim($path);

    if ($path === '' || str_contains($path, "\n") || str_contains($path, "\r")) {
        return 'jogadores.php';
    }

    if (preg_match('#^(https?:)?//#i', $path) === 1) {
        return 'jogadores.php';
    }

    if (str_starts_with($path, '/')) {
        return $path;
    }

    if (preg_match('/^[a-z0-9._-]+\.php(?:\?.*)?$/i', $path) === 1) {
        return $path;
    }

    return 'jogadores.php';
}

function require_auth(): void
{
    if (current_user() !== null) {
        return;
    }

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $uri = $script === 'index.php'
        ? 'jogadores.php'
        : (string) ($_SERVER['REQUEST_URI'] ?? 'jogadores.php');
    redirect('login.php?redirect=' . urlencode($uri));
}

function is_admin(): bool
{
    $user = current_user();

    if ($user === null) {
        return false;
    }

    return ($user['admin'] ?? false) === true;
}

function require_admin(string $redirectTo = 'jogadores.php'): void
{
    require_auth();

    if (is_admin()) {
        return;
    }

    flash('error', 'Acesso restrito a administradores.');
    redirect($redirectTo);
}

if (!is_public_page()) {
    require_auth();
}
