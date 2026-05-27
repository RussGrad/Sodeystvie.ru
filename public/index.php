<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

// Отладка только при SITE_ALLOW_DEBUG=true в public/.env (на проде держите false)
if (isset($_GET['phpinfo']) && site_allow_debug_endpoints()) {
    phpinfo();
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$pdoPgsql = extension_loaded('pdo_pgsql');
$dbTestResult = null;
$dbTestError = null;
$showDbTest = site_allow_debug_endpoints() && isset($_GET['dbtest']) && $_GET['dbtest'] === '1';

if ($showDbTest) {
    if (!$pdoPgsql) {
        $dbTestError = 'Расширение pdo_pgsql не загружено.';
    } else {
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '5432';
        $db = getenv('DB_DATABASE') ?: 'sodeystvie';
        $user = getenv('DB_USERNAME') ?: 'sodeystvie';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db);
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $ver = $pdo->query('SELECT version()')->fetchColumn();
            $dbTestResult = is_string($ver) ? $ver : 'OK';
        } catch (Throwable $e) {
            $dbTestError = $e->getMessage();
        }
    }
}

$pageTitle = site_format_page_title();
$currentNav = 'home';
require_once __DIR__ . '/includes/crm-listing-helpers.php';
$preloadLcpImage = site_hero_lcp_preload_href();

require __DIR__ . '/includes/header.php';
?>
<main class="page-main" id="main">
    <?php require __DIR__ . '/includes/section-hero.php'; ?>
    <?php require __DIR__ . '/includes/section-featured-listings.php'; ?>
    <?php require __DIR__ . '/includes/section-services.php'; ?>
    <?php require __DIR__ . '/includes/section-reviews.php'; ?>
<?php if ($showDbTest) { ?>
    <div class="container">
    <section class="page-main__dev" aria-label="Проверка окружения">
        <p>PostgreSQL (PDO): <?php echo $pdoPgsql ? '<strong>pdo_pgsql</strong> подключён' : 'нет'; ?>.</p>
        <p>Локальная проверка соединения: <a href="?dbtest=1">?dbtest=1</a> (только при SITE_ALLOW_DEBUG=true)</p>
    <?php if ($dbTestResult !== null) { ?>
        <p class="page-main__ok">БД: соединение OK.<br><small><?php echo htmlspecialchars($dbTestResult, ENT_QUOTES, 'UTF-8'); ?></small></p>
    <?php } ?>
    <?php if ($dbTestError !== null) { ?>
        <p class="page-main__err">Ошибка БД: <?php echo htmlspecialchars($dbTestError, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>
    </section>
    </div>
<?php } ?>
</main>
<?php
require __DIR__ . '/includes/footer.php';
