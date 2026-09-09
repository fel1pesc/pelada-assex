<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $content */
/** @var bool $loginPage */
$loginPage = $loginPage ?? false;
$page = current_page();
$flash = take_flash();
$user = current_user();
$isAdmin = is_admin();

if (!function_exists('nav_icon')) {
    function nav_icon(string $name): string
    {
        $icons = [
            'jogadores' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'peladas' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
            'relatorio' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20V10M18 20V4M6 20v-4"/></svg>',
            'usuarios' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            'menu' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
            'close' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>',
            'collapse' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>',
        ];

        return $icons[$name] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · <?= h(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/pt-BR.js"></script>
    <?php if (!$loginPage): ?>
        <script>
            try {
                if (localStorage.getItem('assex-sidebar-collapsed') === '1' && window.matchMedia('(min-width: 901px)').matches) {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        </script>
    <?php endif; ?>
</head>
<body class="<?= $loginPage ? 'is-login' : '' ?>">
    <div class="pitch"></div>
    <?php if ($loginPage): ?>
        <main class="wrap login-wrap">
            <?php if ($flash): ?>
                <p class="flash flash-<?= h((string) $flash['type']) ?>"><?= h((string) $flash['message']) ?></p>
            <?php endif; ?>
            <?= $content ?>
        </main>
    <?php else: ?>
        <div class="app">
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-head">
                    <a class="brand" href="jogadores.php">
                        <span class="brand-mark">AX</span>
                        <span class="brand-copy">
                            <strong><?= h(APP_NAME) ?></strong>
                            <small>Elenco e ranking</small>
                        </span>
                    </a>
                    <button class="icon-btn sidebar-close" type="button" id="sidebar-close" aria-label="Fechar menu">
                        <?= nav_icon('close') ?>
                    </button>
                </div>
                <nav class="sidebar-nav" aria-label="Principal">
                    <a class="<?= $page === 'jogadores' ? 'is-active' : '' ?>" href="jogadores.php" title="Jogadores">
                        <?= nav_icon('jogadores') ?>
                        <span class="nav-label">Jogadores</span>
                    </a>
                    <a class="<?= $page === 'peladas' ? 'is-active' : '' ?>" href="peladas.php" title="Peladas">
                        <?= nav_icon('peladas') ?>
                        <span class="nav-label">Peladas</span>
                    </a>
                    <a class="<?= $page === 'relatorio' ? 'is-active' : '' ?>" href="relatorio.php" title="Relatório">
                        <?= nav_icon('relatorio') ?>
                        <span class="nav-label">Relatório</span>
                    </a>
                    <?php if ($isAdmin): ?>
                        <a class="<?= $page === 'usuarios' ? 'is-active' : '' ?>" href="usuarios.php" title="Usuários">
                            <?= nav_icon('usuarios') ?>
                            <span class="nav-label">Usuários</span>
                        </a>
                    <?php endif; ?>
                </nav>
                <button class="sidebar-collapse" type="button" id="sidebar-collapse" aria-pressed="false" title="Recolher menu">
                    <?= nav_icon('collapse') ?>
                    <span class="nav-label">Recolher</span>
                </button>
            </aside>
            <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>

            <div class="app-body">
                <header class="topbar">
                    <button class="icon-btn menu-toggle" type="button" id="menu-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Abrir menu">
                        <?= nav_icon('menu') ?>
                    </button>
                    <a class="brand brand-mobile" href="jogadores.php">
                        <span class="brand-mark">AX</span>
                        <span class="brand-copy">
                            <strong><?= h(APP_NAME) ?></strong>
                        </span>
                    </a>
                    <?php if ($user !== null): ?>
                        <div class="session">
                            <span class="session-name"><?= h((string) $user['nome']) ?></span>
                            <form method="post" action="logout.php">
                                <?= csrf_field() ?>
                                <button class="btn secondary" type="submit">Sair</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </header>

                <main class="wrap">
                    <?php if ($flash): ?>
                        <p class="flash flash-<?= h((string) $flash['type']) ?>"><?= h((string) $flash['message']) ?></p>
                    <?php endif; ?>
                    <?= $content ?>
                </main>
            </div>
        </div>
        <script>
            (() => {
                const html = document.documentElement;
                const sidebar = document.getElementById('sidebar');
                const collapseBtn = document.getElementById('sidebar-collapse');
                const menuToggle = document.getElementById('menu-toggle');
                const sidebarClose = document.getElementById('sidebar-close');
                const backdrop = document.getElementById('sidebar-backdrop');
                const storageKey = 'assex-sidebar-collapsed';
                const desktopMq = window.matchMedia('(min-width: 901px)');

                const setCollapsed = (collapsed) => {
                    html.classList.toggle('sidebar-collapsed', collapsed && desktopMq.matches);
                    collapseBtn?.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
                    collapseBtn?.setAttribute('title', collapsed ? 'Expandir menu' : 'Recolher menu');
                    const label = collapseBtn?.querySelector('.nav-label');
                    if (label) label.textContent = collapsed ? 'Expandir' : 'Recolher';
                };

                const setOpen = (open) => {
                    html.classList.toggle('sidebar-open', open);
                    menuToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (backdrop) backdrop.hidden = !open;
                };

                const storedCollapsed = () => {
                    try {
                        return localStorage.getItem(storageKey) === '1';
                    } catch (e) {
                        return false;
                    }
                };

                setCollapsed(storedCollapsed());

                collapseBtn?.addEventListener('click', () => {
                    const next = !html.classList.contains('sidebar-collapsed');
                    try {
                        localStorage.setItem(storageKey, next ? '1' : '0');
                    } catch (e) {}
                    setCollapsed(next);
                });

                menuToggle?.addEventListener('click', () => {
                    setOpen(!html.classList.contains('sidebar-open'));
                });

                const closeDrawer = () => setOpen(false);
                sidebarClose?.addEventListener('click', closeDrawer);
                backdrop?.addEventListener('click', closeDrawer);
                sidebar?.querySelectorAll('.sidebar-nav a').forEach((link) => {
                    link.addEventListener('click', () => {
                        if (!desktopMq.matches) closeDrawer();
                    });
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') closeDrawer();
                });

                desktopMq.addEventListener('change', () => {
                    closeDrawer();
                    setCollapsed(storedCollapsed());
                });
            })();
        </script>
    <?php endif; ?>
</body>
</html>
