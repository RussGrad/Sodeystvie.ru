<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Каталог — Содействие';
$currentNav = 'catalog';

if (isset($_GET['view']) && $_GET['view'] === 'map') {
    $params = $_GET;
    unset($params['view']);
    $query = http_build_query($params);
    header('Location: /catalog/map/' . ($query !== '' ? '?' . $query : ''), true, 302);
    exit;
}

if (isset($_GET['id']) && is_string($_GET['id'])) {
    $id = trim($_GET['id']);
    if ($id !== '' && site_validate_crm_object_id($id)) {
        header('Location: /catalog/object/?id=' . rawurlencode($id), true, 302);
        exit;
    }
}

require_once __DIR__ . '/../includes/crm-listing-helpers.php';
require_once __DIR__ . '/../includes/catalog-filter.php';

$catalogFilters = site_catalog_filters_from_request();
$crmFetched = site_crm_fetch_listings_catalog($catalogFilters, 48);
$crmItems = $crmFetched['items'];
$crmTotal = $crmFetched['total'];
$crmError = $crmFetched['error'];
$crmFiltered = $crmFetched['filtered'];

$catalogCardJsVersion = (string) (@filemtime(__DIR__ . '/../js/catalog-listing-card.js') ?: time());
$filterQuery = http_build_query(array_filter($catalogFilters, static fn ($v) => $v !== ''));

require __DIR__ . '/../includes/header.php';

?>
<main class="page-main page-main--inner" id="main">
    <div class="container">
        <header class="catalog__header">
            <h1 class="page-main__heading">Каталог</h1>
            <p class="page-main__lead">Опубликованные объекты из CRM (стадия «Активный»). Уточните параметры в фильтре слева.</p>
        </header>

        <div class="catalog-layout">
            <aside class="catalog-layout__aside" aria-label="Фильтр каталога">
                <?php site_render_catalog_filter($catalogFilters); ?>
            </aside>

            <section class="catalog-layout__main" aria-labelledby="cat-published-title">
                <div class="catalog__results-head">
                    <h2 class="catalog__title" id="cat-published-title">Объекты</h2>
                    <a class="catalog__map-link" href="/catalog/map/<?php echo $filterQuery !== '' ? '?' . htmlspecialchars($filterQuery, ENT_QUOTES, 'UTF-8') : ''; ?>">На карте</a>
                    <?php if (!$crmError && $crmTotal !== null) { ?>
                        <p class="catalog__count">
                            <?php
                            if ($crmFiltered) {
                                echo 'Найдено: ' . (int) $crmTotal;
                            } else {
                                echo 'Всего: ' . (int) $crmTotal;
                            }
                            ?>
                        </p>
                    <?php } ?>
                </div>

                <?php if ($crmError) { ?>
                    <p class="page-main__lead"><?php echo htmlspecialchars($crmError, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php $envHint = site_crm_env_setup_hint(); if ($envHint !== null) { ?>
                        <p class="page-main__lead"><strong>Настройка:</strong> <?php echo htmlspecialchars($envHint, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
                <?php } elseif (count($crmItems) === 0) { ?>
                    <p class="page-main__lead">
                        <?php if ($crmFiltered) { ?>
                            По выбранным параметрам ничего не найдено. <a href="/catalog/">Сбросить фильтр</a>.
                        <?php } else { ?>
                            Пока нет опубликованных объектов. После модерации они появятся здесь.
                        <?php } ?>
                    </p>
                <?php } else { ?>
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
    </div>
</main>
<script src="/js/catalog-listing-card.js?v=<?php echo htmlspecialchars($catalogCardJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php
require __DIR__ . '/../includes/footer.php';
