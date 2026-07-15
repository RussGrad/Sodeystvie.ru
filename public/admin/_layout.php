<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/site-admin.php';

/**
 * @param 'login'|'app' $variant
 */
function site_admin_render_head(string $title, string $variant = 'app'): void
{
    site_admin_send_noindex();
    $cssVersion = (string) (@filemtime(__DIR__ . '/../css/admin.css') ?: time());
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> — Админка <?php echo htmlspecialchars(site_brand_full(), ENT_QUOTES, 'UTF-8'); ?></title>
    <script>
    (function () {
        try {
            var key = 'sodeystvie-admin-theme';
            var stored = localStorage.getItem(key);
            var theme = (stored === 'light' || stored === 'dark')
                ? stored
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        } catch (e) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
    </script>
    <link rel="stylesheet" href="/css/admin.css?v=<?php echo htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="admin-body admin-body--<?php echo htmlspecialchars($variant, ENT_QUOTES, 'UTF-8'); ?>">
    <?php
}

function site_admin_render_theme_toggle(): void
{
    ?>
    <button type="button" class="admin-theme-toggle" data-admin-theme-toggle aria-label="Включить светлую тему">
        <svg class="admin-theme-toggle__icon admin-theme-toggle__icon--moon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg class="admin-theme-toggle__icon admin-theme-toggle__icon--sun" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" hidden>
            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/>
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </button>
    <?php
}

function site_admin_render_nav(string $active = ''): void
{
    $items = [
        '' => ['href' => '/admin/', 'label' => 'Обзор'],
        'settings' => ['href' => '/admin/edit.php?section=settings', 'label' => 'Контакты и тексты'],
        'team' => ['href' => '/admin/edit.php?section=team', 'label' => 'Команда'],
        'reviews' => ['href' => '/admin/edit.php?section=reviews', 'label' => 'Отзывы'],
        'cases' => ['href' => '/admin/edit.php?section=cases', 'label' => 'Кейсы'],
        'services' => ['href' => '/admin/edit.php?section=services', 'label' => 'Услуги'],
        'vacancies' => ['href' => '/admin/edit.php?section=vacancies', 'label' => 'Вакансии'],
    ];
    ?>
    <header class="admin-topbar">
        <div class="admin-topbar__inner">
            <a class="admin-topbar__brand" href="/admin/">Админка · <?php echo htmlspecialchars(site_brand_name(), ENT_QUOTES, 'UTF-8'); ?></a>
            <nav class="admin-topbar__nav" aria-label="Разделы админки">
                <?php foreach ($items as $key => $item) { ?>
                    <a class="admin-topbar__link<?php echo $active === $key ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php } ?>
            </nav>
            <div class="admin-topbar__actions">
                <?php site_admin_render_theme_toggle(); ?>
                <a class="admin-topbar__site" href="/" target="_blank" rel="noopener noreferrer">Открыть сайт</a>
                <a class="admin-topbar__logout" href="/admin/logout.php">Выйти</a>
            </div>
        </div>
    </header>
    <?php
}

function site_admin_render_foot(): void
{
    ?>
<script>
(function () {
    var key = 'sodeystvie-admin-theme';

    function isDarkTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function syncToggle(btn) {
        var dark = isDarkTheme();
        var moon = btn.querySelector('.admin-theme-toggle__icon--moon');
        var sun = btn.querySelector('.admin-theme-toggle__icon--sun');
        if (moon) {
            moon.hidden = !dark;
        }
        if (sun) {
            sun.hidden = dark;
        }
        btn.setAttribute(
            'aria-label',
            dark ? 'Включить светлую тему' : 'Включить тёмную тему'
        );
    }

    function applyTheme(theme) {
        var next = theme === 'dark' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        try {
            localStorage.setItem(key, next);
        } catch (e) {}
        document.querySelectorAll('[data-admin-theme-toggle]').forEach(syncToggle);
    }

    document.querySelectorAll('[data-admin-theme-toggle]').forEach(function (btn) {
        syncToggle(btn);
        btn.addEventListener('click', function () {
            applyTheme(isDarkTheme() ? 'light' : 'dark');
        });
    });
})();
</script>
</body>
</html>
    <?php
}

function site_admin_flash_get(): ?string
{
    site_admin_session_start();
    if (empty($_SESSION['admin_flash'])) {
        return null;
    }
    $msg = (string) $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);

    return $msg;
}

function site_admin_flash_set(string $message): void
{
    site_admin_session_start();
    $_SESSION['admin_flash'] = $message;
}
