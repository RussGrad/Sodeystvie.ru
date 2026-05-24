<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/crm-listing-helpers.php';

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

$title = !$error && isset($obj['title']) ? (string) $obj['title'] : 'Объект';
$pageTitle = $title . ' — Каталог — Содействие';
$currentNav = 'catalog';

require __DIR__ . '/../../includes/header.php';

function v_str(array $a, string $k): ?string
{
    if (!array_key_exists($k, $a)) return null;
    if ($a[$k] === null) return null;
    return is_string($a[$k]) ? $a[$k] : (string) $a[$k];
}

function v_num(array $a, string $k): ?float
{
    if (!array_key_exists($k, $a)) return null;
    if ($a[$k] === null) return null;
    if (is_int($a[$k]) || is_float($a[$k])) return (float) $a[$k];
    if (is_string($a[$k]) && is_numeric($a[$k])) return (float) $a[$k];
    return null;
}

function fmt_rub(?string $raw): string
{
    if ($raw === null || $raw === '') return '—';
    $n = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $raw));
    if ($n <= 0) return '—';
    return number_format((int) round($n), 0, '.', ' ') . ' ₽';
}

function object_type_label(?string $objectType, ?int $rooms): string
{
    $t = $objectType ? trim($objectType) : '';
    if ($t === 'flat') {
        if ($rooms !== null && $rooms > 0) return $rooms . '-комнатная квартира';
        return 'Квартира';
    }
    if ($t === 'house') return 'Дом';
    if ($t === 'plot' || $t === 'land') return 'Участок';
    if ($t === 'commercial') return 'Коммерческая недвижимость';
    return 'Объект';
}

$address = !$error ? (v_str($obj, 'address') ?? '') : '';
$city = !$error ? (v_str($obj, 'city') ?? '') : '';
$district = !$error ? (v_str($obj, 'districtValue') ?? '') : '';
$resComplex = !$error ? (v_str($obj, 'residentialComplex') ?? '') : '';
$rooms = !$error && isset($obj['rooms']) && is_numeric($obj['rooms']) ? (int) $obj['rooms'] : null;
$areaTotal = !$error ? v_num($obj, 'areaTotal') : null;
$areaLand = !$error ? v_num($obj, 'areaLand') : null;
$floor = !$error ? (v_str($obj, 'floor') ?? '') : '';
$floorTotal = !$error && isset($obj['floorTotal']) && is_numeric($obj['floorTotal']) ? (int) $obj['floorTotal'] : null;
$priceRaw = !$error ? v_str($obj, 'price') : null;
$objectTypeValue = !$error ? v_str($obj, 'objectTypeValue') : null;
$description = !$error ? (v_str($obj, 'description') ?? '') : '';
if (!$error && trim($description) === '' && is_array($obj) && isset($obj['id'])) {
    $fromApi = site_crm_fetch_listing_description((string) $obj['id']);
    if ($fromApi !== null) {
        $description = $fromApi;
    }
}

$photos = [];
if (!$error && is_array($obj)) {
    $photos = site_crm_listing_resolved_photos($obj, 30);
}
$typeLabel = object_type_label($objectTypeValue, $rooms);
$priceText = fmt_rub($priceRaw);
$subtitleParts = [];
if ($typeLabel !== '') $subtitleParts[] = $typeLabel;
if ($city !== '') $subtitleParts[] = $city;
if ($district !== '') $subtitleParts[] = $district;
$subtitle = implode(' · ', $subtitleParts);
?>

<main class="page-main page-main--inner" id="main">
    <div class="container">
        <nav class="listing__crumbs" aria-label="Хлебные крошки">
            <a class="listing__crumb" href="/catalog/">Каталог</a>
            <span class="listing__crumb-sep" aria-hidden="true">/</span>
            <span class="listing__crumb listing__crumb--current"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>

        <?php if ($error) { ?>
            <h1 class="page-main__heading">Объект</h1>
            <p class="page-main__lead"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } else { ?>
            <header class="listing__header">
                <h1 class="listing__title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
                <?php if ($subtitle !== '') { ?>
                    <p class="listing__subtitle"><?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php } ?>
            </header>

            <section class="listing__layout">
                <div class="listing__gallery" data-gallery>
                    <div class="listing-gallery">
                        <div class="listing-gallery__track" data-gallery-track>
                            <?php if (count($photos) > 0) { ?>
                                <?php foreach ($photos as $idx => $src) { ?>
                                    <figure class="listing-gallery__slide" data-gallery-slide>
                                        <?php echo site_crm_photo_img($src, $title, 'listing-gallery__img'); ?>
                                    </figure>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="listing-gallery__placeholder" aria-label="Фото отсутствует"></div>
                            <?php } ?>
                        </div>

                        <button class="listing-gallery__nav listing-gallery__nav--prev" type="button" data-gallery-prev aria-label="Предыдущее фото">
                            <span aria-hidden="true">‹</span>
                        </button>
                        <button class="listing-gallery__nav listing-gallery__nav--next" type="button" data-gallery-next aria-label="Следующее фото">
                            <span aria-hidden="true">›</span>
                        </button>

                        <div class="listing-gallery__counter" data-gallery-counter aria-live="polite"></div>
                    </div>
                </div>

                <aside class="listing__side">
                    <div class="listing-side__card">
                        <p class="listing-side__price"><?php echo htmlspecialchars($priceText, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if ($address !== '') { ?>
                            <p class="listing-side__address"><?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php } ?>
                        <div class="listing-side__actions">
                            <button class="btn btn--primary" type="button" data-lead-open>Оставить заявку</button>
                            <a class="btn btn--ghost" href="/mortgage/#calculator">Рассчитать ипотеку</a>
                        </div>
                    </div>

                    <section class="listing-side__section" aria-labelledby="listing-params-title">
                        <h2 class="listing-side__section-title" id="listing-params-title">Характеристики</h2>
                        <dl class="listing-params">
                            <div class="listing-params__row">
                                <dt>Тип</dt>
                                <dd><?php echo htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <?php if ($rooms !== null) { ?>
                                <div class="listing-params__row">
                                    <dt>Комнат</dt>
                                    <dd><?php echo (int) $rooms; ?></dd>
                                </div>
                            <?php } ?>
                            <?php if ($areaTotal !== null) { ?>
                                <div class="listing-params__row">
                                    <dt>Площадь</dt>
                                    <dd><?php echo htmlspecialchars(rtrim(rtrim(number_format($areaTotal, 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8'); ?> м²</dd>
                                </div>
                            <?php } ?>
                            <?php if ($areaLand !== null) { ?>
                                <div class="listing-params__row">
                                    <dt>Участок</dt>
                                    <dd><?php echo htmlspecialchars(rtrim(rtrim(number_format($areaLand, 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8'); ?> сот.</dd>
                                </div>
                            <?php } ?>
                            <?php if ($floor !== '') { ?>
                                <div class="listing-params__row">
                                    <dt>Этаж</dt>
                                    <dd>
                                        <?php
                                        $ft = $floorTotal ? (' / ' . $floorTotal) : '';
                                        echo htmlspecialchars($floor . $ft, ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </dd>
                                </div>
                            <?php } ?>
                            <?php if ($resComplex !== '') { ?>
                                <div class="listing-params__row">
                                    <dt>Жилой комплекс</dt>
                                    <dd><?php echo htmlspecialchars($resComplex, ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                            <?php } ?>
                        </dl>
                    </section>
                </aside>
            </section>

            <?php if ($description !== '') { ?>
                <section class="listing__section" aria-labelledby="listing-desc-title">
                    <h2 class="listing__section-title" id="listing-desc-title">Описание</h2>
                    <div class="listing__desc">
                        <?php echo nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')); ?>
                    </div>
                </section>
            <?php } ?>
        <?php } ?>
    </div>
</main>

<?php
require __DIR__ . '/../../includes/footer.php';

