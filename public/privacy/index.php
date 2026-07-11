<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/site-legal.php';

$pageTitle = site_format_page_title('Политика конфиденциальности');
$currentNav = '';
$privacyParagraphs = site_privacy_policy_paragraphs();

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner page-main--legal-text" id="main">
    <div class="container">
        <h1 class="page-main__heading">Политика конфиденциальности</h1>
        <?php if (count($privacyParagraphs) > 0) { ?>
            <div class="legal-text">
                <?php foreach ($privacyParagraphs as $paragraph) { ?>
                    <p class="legal-text__paragraph"><?php echo nl2br(htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8')); ?></p>
                <?php } ?>
            </div>
        <?php } else { ?>
            <p class="page-main__lead">Текст политики конфиденциальности будет опубликован после согласования.</p>
        <?php } ?>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';
