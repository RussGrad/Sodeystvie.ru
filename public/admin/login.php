<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

site_admin_session_start();

if (site_admin_is_logged_in()) {
    header('Location: /admin/', true, 302);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? (string) $_POST['username'] : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    if (!site_admin_is_configured()) {
        $error = 'Админка не настроена. Добавьте SITE_ADMIN_LOGIN и пароль в .env на сервере.';
    } elseif (site_admin_login($username, $password)) {
        header('Location: /admin/', true, 302);
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}

site_admin_render_head('Вход', 'login');
?>
<main class="admin-login">
    <div class="admin-login__card">
        <h1 class="admin-login__title">Вход в админку сайта</h1>
        <p class="admin-login__lead"><?php echo htmlspecialchars(site_brand_full(), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($error !== '') { ?>
            <p class="admin-login__error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
        <?php if (!site_admin_is_configured()) { ?>
            <p class="admin-login__hint">На сервере в <code>public/.env</code> задайте:<br><code>SITE_ADMIN_LOGIN=...</code><br><code>SITE_ADMIN_PASSWORD_HASH=...</code></p>
        <?php } else { ?>
            <form class="admin-login__form" method="post" action="/admin/login.php" autocomplete="on">
                <label class="admin-field">
                    <span class="admin-field__label">Логин</span>
                    <input class="admin-field__input" type="text" name="username" required autocomplete="username">
                </label>
                <label class="admin-field">
                    <span class="admin-field__label">Пароль</span>
                    <input class="admin-field__input" type="password" name="password" required autocomplete="current-password">
                </label>
                <button class="admin-btn admin-btn--primary" type="submit">Войти</button>
            </form>
        <?php } ?>
        <p class="admin-login__back"><a href="/">← На сайт</a></p>
    </div>
</main>
<?php
site_admin_render_foot();
