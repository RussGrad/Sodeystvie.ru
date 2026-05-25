<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

$pageTitle = site_format_page_title('Контакты');
$currentNav = 'contacts';

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner" id="main">
    <div class="container">
        <h1 class="page-main__heading">Контакты</h1>
        <p class="page-main__lead">
            Телефон: <a href="tel:<?php echo htmlspecialchars(SITE_PHONE_TEL, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(SITE_PHONE_DISPLAY, ENT_QUOTES, 'UTF-8'); ?></a><br>
            Email: <a href="mailto:<?php echo htmlspecialchars(SITE_EMAIL, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(SITE_EMAIL, ENT_QUOTES, 'UTF-8'); ?></a><br>
            Адрес: <?php echo htmlspecialchars(SITE_ADDRESS, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';

