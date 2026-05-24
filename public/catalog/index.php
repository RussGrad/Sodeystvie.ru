<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Каталог — Содействие';
$currentNav = 'catalog';

if (isset($_GET['id']) && is_string($_GET['id'])) {
    $id = trim($_GET['id']);
    if ($id !== '' && site_validate_crm_object_id($id)) {
        header('Location: /catalog/object/?id=' . rawurlencode($id), true, 302);
        exit;
    }
}

require_once __DIR__ . '/../includes/crm-listing-helpers.php';

require __DIR__ . '/../includes/header.php';

/** Публичные объекты CRM (стадия «Активный»). */
$crmFetched = site_crm_fetch_listings(24, 0);
$crmItems = $crmFetched['items'];
$crmTotal = $crmFetched['total'];
$crmError = $crmFetched['error'];

$catalogCardJsVersion = (string) (@filemtime(__DIR__ . '/../js/catalog-listing-card.js') ?: time());

?>
<main class="page-main page-main--inner" id="main">
    <div class="container">
        <header class="catalog__header">
            <h1 class="page-main__heading">Каталог</h1>
            <p class="page-main__lead">Опубликованные объекты подтягиваются из CRM (стадия «Активный»).</p>
        </header>

        <section class="catalog__section" aria-labelledby="cat-published-title">
            <h2 class="catalog__title" id="cat-published-title">Опубликованные объекты</h2>
            <?php if ($crmError) { ?>
                <p class="page-main__lead"><?php echo htmlspecialchars($crmError, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php $envHint = site_crm_env_setup_hint(); if ($envHint !== null) { ?>
                    <p class="page-main__lead"><strong>Настройка:</strong> <?php echo htmlspecialchars($envHint, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php } ?>
            <?php } ?>
            <?php if (!$crmError && $crmTotal !== null && $crmTotal === 0) { ?>
                <p class="page-main__lead">Пока нет опубликованных объектов (стадия «Активный»). После модерации они появятся здесь.</p>
            <?php } ?>
            <?php if (!$crmError && count($crmItems) > 0) { ?>
                <ul class="catalog-list">
                    <?php foreach ($crmItems as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        site_render_catalog_listing_card($row);
                    } ?>
                </ul>
            <?php } ?>
        </section>

    </div>
</main>
<script src="/js/catalog-listing-card.js?v=<?php echo htmlspecialchars($catalogCardJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php
require __DIR__ . '/../includes/footer.php';
