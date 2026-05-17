<?php

declare(strict_types=1);

$pageTitle = 'Каталог — Содействие';
$currentNav = 'catalog';

if (isset($_GET['id']) && is_string($_GET['id']) && trim($_GET['id']) !== '') {
    $id = trim($_GET['id']);
    header('Location: /catalog/object/?id=' . rawurlencode($id), true, 302);
    exit;
}

require_once __DIR__ . '/../includes/crm-listing-helpers.php';

require __DIR__ . '/../includes/header.php';

/** Публичные объекты CRM (стадия «Активный»). */
$crmFetched = site_crm_fetch_listings(60, 0);
$crmItems = $crmFetched['items'];
$crmTotal = $crmFetched['total'];
$crmError = $crmFetched['error'];

function fmt_rub(?string $raw): string
{
    return site_fmt_rub($raw);
}

function fmt_m2(?float $areaTotal, ?string $priceRaw): ?string
{
    return site_fmt_m2($areaTotal, $priceRaw);
}

function estimate_mortgage_monthly(?string $priceRaw, float $downPercent = 0.2, float $rateYear = 12.5, int $years = 20): ?string
{
    if ($priceRaw === null || $priceRaw === '') {
        return null;
    }
    $price = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $priceRaw));
    if ($price <= 0) {
        return null;
    }
    $loan = $price * (1.0 - $downPercent);
    $months = max(1, $years * 12);
    $i = max(0.0, $rateYear) / 100.0 / 12.0;
    if ($loan <= 0) {
        return null;
    }
    if ($i == 0.0) {
        $monthly = $loan / $months;
    } else {
        $p = pow(1.0 + $i, $months);
        $monthly = $loan * ($i * $p) / ($p - 1.0);
    }
    $m = (int) round($monthly);

    return $m <= 0 ? null : 'от ' . number_format($m, 0, '.', ' ') . ' ₽/мес.';
}

function object_meta_label(?string $objectType, ?int $rooms): string
{
    return site_object_meta_label($objectType, $rooms);
}

function tone_from_id(string $id): int
{
    return site_tone_from_id($id);
}

?>
<main class="page-main page-main--inner" id="main">
    <div class="container">
        <header class="catalog__header">
            <h1 class="page-main__heading">Каталог</h1>
            <p class="page-main__lead">Опубликованные объекты подтягиваются из CRM. Ниже также остаются демонстрационные блоки для прототипа.</p>
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
                <ul class="resale-grid">
                    <?php foreach ($crmItems as $row) {
                        if (!is_array($row)) continue;
                        $id = isset($row['id']) ? (string) $row['id'] : '';
                        if ($id === '') continue;
                        $title = isset($row['title']) ? (string) $row['title'] : 'Объект';
                        $address = isset($row['address']) ? (string) $row['address'] : '';
                        $rooms = isset($row['rooms']) && is_numeric($row['rooms']) ? (int) $row['rooms'] : null;
                        $areaTotal = isset($row['areaTotal']) && is_numeric($row['areaTotal']) ? (float) $row['areaTotal'] : null;
                        $floor = isset($row['floor']) ? (string) $row['floor'] : null;
                        $priceRaw = isset($row['price']) ? (string) $row['price'] : null;
                        $objectType = isset($row['objectTypeValue']) ? (string) $row['objectTypeValue'] : null;
                        $coverPhotoRaw = isset($row['coverPhoto']) ? (string) $row['coverPhoto'] : '';
                        $coverPhoto = $coverPhotoRaw !== '' ? site_crm_photo_src($coverPhotoRaw) : '';
                        $photosCount = isset($row['photosCount']) && is_numeric($row['photosCount']) ? (int) $row['photosCount'] : 0;

                        $tone = tone_from_id($id);
                        $meta = object_meta_label($objectType, $rooms);
                        $areaText = $areaTotal ? rtrim(rtrim(number_format($areaTotal, 2, '.', ''), '0'), '.') . ' м²' : '—';
                        $floorText = $floor && trim($floor) !== '' ? $floor : '—';
                        $priceText = fmt_rub($priceRaw);
                        $priceM2 = fmt_m2($areaTotal, $priceRaw);
                        $mortgage = estimate_mortgage_monthly($priceRaw);
                        ?>
                        <li>
                            <article class="resale-card">
                                <a class="resale-card__link" href="/catalog/object/?id=<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
                                    <div
                                        class="resale-card__media resale-card__media--tone-<?php echo (int) $tone; ?>"
                                        aria-hidden="true"
                                        <?php if ($coverPhoto !== '') { ?>
                                            style="background-image: url('<?php echo htmlspecialchars($coverPhoto, ENT_QUOTES, 'UTF-8'); ?>'); background-size: cover; background-position: center;"
                                        <?php } ?>
                                    >
                                        <span class="resale-card__count">1/<?php echo (int) max(1, $photosCount); ?></span>
                                    </div>
                                    <div class="resale-card__body">
                                        <div class="resale-card__top">
                                            <span class="resale-card__pill">Своя ставка от 11.9%</span>
                                            <span class="resale-card__fav" aria-hidden="true">
                                                <svg class="resale-card__fav-icon" viewBox="0 0 24 24">
                                                    <path fill="none" stroke="currentColor" stroke-width="1.8" d="M12 21s-7-4.6-9.5-9C.5 7.5 3.4 4.5 7 4.5c2 0 3.7 1.1 5 2.7 1.3-1.6 3-2.7 5-2.7 3.6 0 6.5 3 4.5 7.5C19 16.4 12 21 12 21Z"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="resale-card__price-row">
                                            <p class="resale-card__price"><?php echo htmlspecialchars($priceText, ENT_QUOTES, 'UTF-8'); ?></p>
                                            <span class="resale-card__trend" aria-hidden="true">
                                                <svg width="22" height="14" viewBox="0 0 22 14">
                                                    <path d="M1 11l6-6 4 4 7-7 3 3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <?php if ($priceM2) { ?>
                                            <p class="resale-card__price-m2"><?php echo htmlspecialchars($priceM2, ENT_QUOTES, 'UTF-8'); ?></p>
                                        <?php } ?>
                                        <?php if ($mortgage) { ?>
                                            <p class="resale-card__mortgage">В ипотеку <span><?php echo htmlspecialchars($mortgage, ENT_QUOTES, 'UTF-8'); ?></span></p>
                                        <?php } ?>
                                        <p class="resale-card__meta">
                                            <span><?php echo htmlspecialchars($meta, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="resale-card__dot" aria-hidden="true"></span>
                                            <span><?php echo htmlspecialchars($areaText, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="resale-card__dot" aria-hidden="true"></span>
                                            <span><?php echo htmlspecialchars($floorText, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </p>
                                        <p class="resale-card__address"><?php echo htmlspecialchars($address !== '' ? $address : $title, ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </a>
                            </article>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </section>

    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';

