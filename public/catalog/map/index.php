<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

$pageTitle = 'Каталог на карте — Содействие';
$currentNav = 'catalog';

require_once __DIR__ . '/../../includes/crm-listing-helpers.php';
require_once __DIR__ . '/../../includes/catalog-filter.php';

$catalogFilters = site_catalog_filters_from_request();
$mapFetched = site_crm_fetch_listings_for_map($catalogFilters);
$mapItems = $mapFetched['items'];
$mapError = $mapFetched['error'];
$mapTotal = $mapFetched['total'];
$mapWithCoords = $mapFetched['withCoords'];
$mapMarkers = site_catalog_map_markers_from_items($mapItems);
$mapCenter = site_map_default_center();
$yandexMapsKey = site_yandex_maps_api_key();

$markersJson = json_encode($mapMarkers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
$centerJson = json_encode([
    'lat' => $mapCenter['lat'],
    'lng' => $mapCenter['lng'],
    'zoom' => $mapCenter['zoom'],
], JSON_UNESCAPED_UNICODE) ?: '{}';

$filterQuery = http_build_query(array_filter($catalogFilters, static fn ($v) => $v !== ''));
$listHref = '/catalog/' . ($filterQuery !== '' ? '?' . $filterQuery : '');

$catalogMapJsVersion = (string) (@filemtime(__DIR__ . '/../../js/catalog-map.js') ?: time());

require __DIR__ . '/../../includes/header.php';

?>
<main class="catalog-map-page" id="main">
    <div class="catalog-map-page__toolbar">
        <div class="container catalog-map-page__toolbar-inner">
            <div class="catalog-map-page__nav">
                <a class="catalog-map-page__back" href="<?php echo htmlspecialchars($listHref, ENT_QUOTES, 'UTF-8'); ?>">← Список</a>
                <h1 class="catalog-map-page__title">Объекты на карте</h1>
            </div>
            <?php if (!$mapError && $mapTotal !== null) { ?>
                <p class="catalog-map-page__stat">
                    На карте: <strong><?php echo (int) count($mapMarkers); ?></strong>
                    <?php if ($mapWithCoords < count($mapItems)) { ?>
                        <span class="catalog-map-page__stat-muted">(ещё <?php echo (int) (count($mapItems) - $mapWithCoords); ?> без координат в CRM)</span>
                    <?php } ?>
                </p>
            <?php } ?>
        </div>
    </div>

    <?php if ($mapError) { ?>
        <div class="container catalog-map-page__message">
            <p><?php echo htmlspecialchars($mapError, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    <?php } elseif ($yandexMapsKey === '') { ?>
        <div class="container catalog-map-page__message">
            <p>Для карты укажите ключ <code>YANDEX_MAPS_API_KEY</code> на хостинге (JavaScript API Яндекс.Карт).</p>
            <p>Файл в корне сайта (рядом с <code>index.php</code>), один из вариантов:</p>
            <ul>
                <li><code>crm-config.env</code> — удобнее на REG.RU (без точки в имени);</li>
                <li><code>.env</code> — та же папка;</li>
                <li><code>includes/crm-config.local.php</code> — массив с ключом (см. пример в репозитории).</li>
            </ul>
            <p><a href="https://developer.tech.yandex.ru/">Получить ключ</a> → «JavaScript API».</p>
        </div>
    <?php } elseif (count($mapMarkers) === 0) { ?>
        <div class="container catalog-map-page__message">
            <p>Нет объектов с координатами для отображения. В CRM у активных объектов заполните адрес и нажмите геокодирование в карточке.</p>
            <p><a href="<?php echo htmlspecialchars($listHref, ENT_QUOTES, 'UTF-8'); ?>">Вернуться к списку</a></p>
        </div>
    <?php } else { ?>
        <div
            class="catalog-map-page__canvas"
            id="catalog-map"
            data-markers="<?php echo htmlspecialchars($markersJson, ENT_QUOTES, 'UTF-8'); ?>"
            data-center="<?php echo htmlspecialchars($centerJson, ENT_QUOTES, 'UTF-8'); ?>"
            role="application"
            aria-label="Карта объектов недвижимости"
        ></div>
        <script src="https://api-maps.yandex.ru/2.1/?apikey=<?php echo htmlspecialchars($yandexMapsKey, ENT_QUOTES, 'UTF-8'); ?>&amp;lang=ru_RU"></script>
        <script src="/js/catalog-map.js?v=<?php echo htmlspecialchars($catalogMapJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php } ?>

    <details class="catalog-map-page__filters container">
        <summary class="catalog-map-page__filters-toggle">Фильтр</summary>
        <?php site_render_catalog_filter($catalogFilters, '/catalog/map/'); ?>
    </details>
</main>
<?php
require __DIR__ . '/../../includes/footer.php';
