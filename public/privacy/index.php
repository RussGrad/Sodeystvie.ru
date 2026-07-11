<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/site-legal.php';

$pageTitle = site_format_page_title('Политика конфиденциальности');
$currentNav = '';

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner page-main--legal-text" id="main">
    <div class="container">
        <h1 class="page-main__heading">Политика конфиденциальности</h1>
        <?php site_privacy_policy_render(); ?>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';
