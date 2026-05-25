<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/crm-listing-helpers.php';
require_once __DIR__ . '/../../includes/listing-gallery.php';

$id = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';
if ($id === '' || !site_validate_crm_object_id($id)) {
    header('Location: /catalog/', true, 302);
    exit;
}

$apiUrl = site_crm_listings_url($id);
$obj = site_http_get_json_cached($apiUrl, 10, 900);
$error = isset($obj['_error']) ? (string) $obj['_error'] : null;

if (!$error && is_array($obj)) {
    $obj = site_crm_listing_enrich_row($obj);
}

if (!$error && (!is_array($obj) || !isset($obj['id']))) {
    $error = 'Объект не найден';
}

$titleRaw = !$error && isset($obj['title']) ? (string) $obj['title'] : 'Объект';
$rooms = !$error && isset($obj['rooms']) && is_numeric($obj['rooms']) ? (int) $obj['rooms'] : null;
$areaTotal = !$error && isset($obj['areaTotal']) && is_numeric($obj['areaTotal']) ? (float) $obj['areaTotal'] : null;
$objectTypeValue = !$error ? (isset($obj['objectTypeValue']) ? (string) $obj['objectTypeValue'] : null) : null;
$headingTitle = site_listing_card_title($objectTypeValue, $rooms, $areaTotal, $titleRaw);

$pageTitle = site_format_page_title($headingTitle);
$currentNav = 'catalog';
$galleryBundle = ['first' => '', 'raw' => [], 'total' => 0];
$preloadLcpImage = '';
$listingMapScripts = false;
$yandexMapsKey = site_yandex_maps_api_key();

if (!$error && is_array($obj)) {
    $galleryBundle = site_crm_listing_gallery_bundle($obj, 30);
    $preloadLcpImage = $galleryBundle['first'];
}

require __DIR__ . '/../../includes/header.php';

if ($error) {
    ?>
    <main class="page-main page-main--inner" id="main">
        <div class="container">
            <nav class="listing__crumbs" aria-label="Хлебные крошки">
                <a class="listing__crumb" href="/catalog/">Каталог</a>
                <span class="listing__crumb-sep" aria-hidden="true">/</span>
                <span class="listing__crumb listing__crumb--current">Объект</span>
            </nav>
            <h1 class="page-main__heading">Объект</h1>
            <p class="page-main__lead"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </main>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

$priceRaw = isset($obj['price']) ? (string) $obj['price'] : null;
$priceText = site_fmt_rub($priceRaw);
$priceM2 = site_fmt_m2($areaTotal, $priceRaw);
$addressLine = site_listing_address_line($obj);
$district = isset($obj['districtValue']) ? trim((string) $obj['districtValue']) : '';
$dealLine = site_deal_line_public_label(isset($obj['dealLineValue']) ? (string) $obj['dealLineValue'] : null);
$description = isset($obj['description']) ? trim((string) $obj['description']) : '';
$contactPhone = isset($obj['contactPhone']) ? (string) $obj['contactPhone'] : null;
$phoneDisplay = site_mask_phone_display($contactPhone);
$phoneTel = site_mask_phone_tel($contactPhone);
$paramRows = site_listing_object_param_rows($obj);

$lat = isset($obj['latitude']) && is_numeric($obj['latitude']) ? (float) $obj['latitude'] : null;
$lng = isset($obj['longitude']) && is_numeric($obj['longitude']) ? (float) $obj['longitude'] : null;
$hasMapCoords = $lat !== null && $lng !== null
    && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180
    && (abs($lat) > 0.0001 || abs($lng) > 0.0001);

$mapsExternalUrl = $hasMapCoords
    ? ('https://yandex.ru/maps/?pt=' . $lng . ',' . $lat . '&z=16&l=map')
    : ($addressLine !== '' ? 'https://yandex.ru/maps/?text=' . rawurlencode($addressLine) : '');

$markerJson = json_encode([
    'lat' => $hasMapCoords ? $lat : null,
    'lng' => $hasMapCoords ? $lng : null,
    'zoom' => 16,
    'title' => $headingTitle,
    'address' => $addressLine,
    'price' => $priceText,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

$metaParts = array_filter([$dealLine, $district], static fn (string $v): bool => $v !== '');
$metaLine = implode(' · ', $metaParts);

$listingMapScripts = $hasMapCoords && $yandexMapsKey !== '';
$listingObjectMapJsVersion = (string) (@filemtime(__DIR__ . '/../../js/listing-object-map.js') ?: time());
?>

<main class="page-main page-main--inner listing-object-page" id="main">
    <div class="container">
        <nav class="listing__crumbs" aria-label="Хлебные крошки">
            <a class="listing__crumb" href="/catalog/">Каталог</a>
            <span class="listing__crumb-sep" aria-hidden="true">/</span>
            <span class="listing__crumb listing__crumb--current"><?php echo htmlspecialchars($headingTitle, ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>

        <article class="listing-object" data-listing-object data-listing-id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <header class="listing-object__top">
                <div class="listing-object__intro">
                    <h1 class="listing-object__title"><?php echo htmlspecialchars($headingTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <?php if ($addressLine !== '') { ?>
                        <?php if ($mapsExternalUrl !== '') { ?>
                            <a class="listing-object__address" href="<?php echo htmlspecialchars($mapsExternalUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($addressLine, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php } else { ?>
                            <p class="listing-object__address"><?php echo htmlspecialchars($addressLine, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php } ?>
                    <?php } ?>
                    <?php if ($metaLine !== '') { ?>
                        <p class="listing-object__meta"><?php echo htmlspecialchars($metaLine, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
                </div>
                <div class="listing-object__buy">
                    <p class="listing-object__price"><?php echo htmlspecialchars($priceText, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($priceM2 !== null) { ?>
                        <p class="listing-object__price-m2"><?php echo htmlspecialchars($priceM2, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
                    <div class="listing-object__actions">
                        <button class="btn btn--primary" type="button" data-lead-open>Оставить заявку</button>
                        <?php if ($phoneTel !== '') { ?>
                            <a class="btn btn--ghost listing-object__phone-btn" href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $phoneTel) ?? $phoneTel, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php } ?>
                        <button type="button" class="listing-object__fav" data-listing-fav aria-pressed="false" aria-label="В избранное">
                            <svg class="listing-object__fav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-width="1.8" d="M12 21s-7-4.6-9.5-9C.5 7.5 3.4 4.5 7 4.5c2 0 3.7 1.1 5 2.7 1.3-1.6 3-2.7 5-2.7 3.6 0 6.5 3 4.5 7.5C19 16.4 12 21 12 21Z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <div class="listing-object__gallery-wrap">
                <?php site_render_listing_gallery($galleryBundle, $headingTitle); ?>
            </div>

            <?php if (count($paramRows) > 0) { ?>
                <section class="listing-object__section" aria-labelledby="listing-params-title">
                    <h2 class="listing-object__section-title" id="listing-params-title">Характеристики</h2>
                    <dl class="listing-params listing-params--object">
                        <?php foreach ($paramRows as $row) { ?>
                            <div class="listing-params__row">
                                <dt><?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?></dt>
                                <dd><?php echo htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                        <?php } ?>
                    </dl>
                </section>
            <?php } ?>

            <?php if ($description !== '') { ?>
                <section class="listing-object__section" aria-labelledby="listing-desc-title">
                    <h2 class="listing-object__section-title" id="listing-desc-title">Описание</h2>
                    <div class="listing-object__desc">
                        <?php echo nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')); ?>
                    </div>
                </section>
            <?php } ?>

            <section class="listing-object__section" aria-labelledby="listing-map-title">
                <h2 class="listing-object__section-title" id="listing-map-title">Расположение</h2>
                <div class="listing-object__map-block">
                    <?php if ($listingMapScripts) { ?>
                        <div
                            class="listing-object__map-canvas"
                            id="listing-object-map"
                            data-marker="<?php echo htmlspecialchars($markerJson, ENT_QUOTES, 'UTF-8'); ?>"
                            role="img"
                            aria-label="Карта расположения объекта"
                        ></div>
                    <?php } else { ?>
                        <div class="listing-object__map-fallback">
                            <?php if ($yandexMapsKey === '' && $hasMapCoords) { ?>
                                <p>Интерактивная карта подключается ключом <code>YANDEX_MAPS_API_KEY</code> в <code>.env</code> на хостинге.</p>
                            <?php } elseif ($addressLine !== '') { ?>
                                <p>Точные координаты объекта пока не указаны в CRM. Адрес:</p>
                                <p class="listing-object__map-address"><?php echo htmlspecialchars($addressLine, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php } else { ?>
                                <p>Адрес и координаты объекта не указаны.</p>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <?php if ($mapsExternalUrl !== '') { ?>
                        <a class="listing-object__map-link" href="<?php echo htmlspecialchars($mapsExternalUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Открыть в Яндекс.Картах</a>
                    <?php } ?>
                </div>
            </section>
        </article>
    </div>
</main>

<?php if ($listingMapScripts) { ?>
    <script src="https://api-maps.yandex.ru/2.1/?apikey=<?php echo htmlspecialchars($yandexMapsKey, ENT_QUOTES, 'UTF-8'); ?>&amp;lang=ru_RU"></script>
    <script src="/js/listing-object-map.js?v=<?php echo htmlspecialchars($listingObjectMapJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php } ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
