<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

$showFavoritesOnly = isset($_GET['favorites']) && (string) $_GET['favorites'] === '1';

$pageTitle = site_format_page_title($showFavoritesOnly ? 'Избранное' : 'Каталог');
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

if ($showFavoritesOnly) {
    $crmItems = [];
    $crmTotal = 0;
    $crmError = null;
    $crmFiltered = true;
} else {
    $crmFetched = site_crm_fetch_listings_catalog($catalogFilters, 48);
    $crmItems = $crmFetched['items'];
    $crmTotal = $crmFetched['total'];
    $crmError = $crmFetched['error'];
    $crmFiltered = $crmFetched['filtered'];
}

$catalogCardJsVersion = (string) (@filemtime(__DIR__ . '/../js/catalog-listing-card.js') ?: time());
$filterQuery = http_build_query(array_filter($catalogFilters, static fn ($v) => $v !== ''));
$extraDeferScripts = ['/js/catalog-listing-card.js?v=' . rawurlencode($catalogCardJsVersion)];

require __DIR__ . '/../includes/header.php';

?>
<main class="page-main page-main--inner<?php echo $showFavoritesOnly ? ' catalog-page--favorites' : ''; ?>" id="main">
    <div class="container">
        <header class="catalog__header">
            <h1 class="page-main__heading"><?php echo $showFavoritesOnly ? 'Избранное' : 'Каталог'; ?></h1>
        </header>

        <div class="catalog-layout">
            <?php if (!$showFavoritesOnly) { ?>
            <aside class="catalog-layout__aside" aria-label="Фильтр каталога">
                <?php site_render_catalog_filter($catalogFilters); ?>
            </aside>
            <?php } ?>

            <section class="catalog-layout__main<?php echo $showFavoritesOnly ? ' catalog-layout__main--full' : ''; ?>" aria-labelledby="cat-published-title">
                <div class="catalog__results-head">
                    <h2 class="catalog__title" id="cat-published-title"><?php echo $showFavoritesOnly ? 'Избранное' : 'Объекты'; ?></h2>
                    <?php if (!$showFavoritesOnly) { ?>
                    <a class="catalog__map-link" href="/catalog/map/<?php echo $filterQuery !== '' ? '?' . htmlspecialchars($filterQuery, ENT_QUOTES, 'UTF-8') : ''; ?>">На карте</a>
                    <?php } else { ?>
                    <a class="catalog__map-link" href="/catalog/">Весь каталог</a>
                    <?php } ?>
                    <?php if ($showFavoritesOnly) { ?>
                        <p class="catalog__count">Загрузка…</p>
                    <?php } elseif (!$crmError && $crmTotal !== null) { ?>
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

                <?php if ($showFavoritesOnly) { ?>
                    <p class="page-main__lead catalog-favorites-loading">Загрузка избранного…</p>
                    <p class="page-main__lead catalog-favorites-empty" hidden>В избранном пока ничего нет. <a href="/catalog/">Перейти в каталог</a>.</p>
                    <ul class="catalog-list" id="catalog-list-root" hidden></ul>
                <?php } elseif ($crmError) { ?>
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
                    <ul class="catalog-list" id="catalog-list-root">
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
<?php
require __DIR__ . '/../includes/footer.php';
