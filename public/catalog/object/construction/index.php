<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/crm-listing-helpers.php';

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

$objectTypeValue = !$error && isset($obj['objectTypeValue']) ? (string) $obj['objectTypeValue'] : '';
if (!$error && $objectTypeValue !== 'newbuilding') {
    header('Location: /catalog/object/?id=' . rawurlencode($id), true, 302);
    exit;
}

$titleRaw = !$error && isset($obj['title']) ? (string) $obj['title'] : 'Объект';
$rooms = !$error && isset($obj['rooms']) && is_numeric($obj['rooms']) ? (int) $obj['rooms'] : null;
$areaTotal = !$error && isset($obj['areaTotal']) && is_numeric($obj['areaTotal']) ? (float) $obj['areaTotal'] : null;
$headingTitle = site_listing_card_title($objectTypeValue, $rooms, $areaTotal, $titleRaw);
$complexName = !$error && isset($obj['residentialComplex']) ? trim((string) $obj['residentialComplex']) : '';
$pageHeading = $complexName !== '' ? $complexName : $headingTitle;

$constructionEntries = !$error ? site_construction_progress_entries($obj) : [];
$buildings = site_construction_progress_buildings($constructionEntries);
$periodGroups = site_construction_progress_period_groups($constructionEntries);
$lastUpdate = site_construction_progress_last_update($constructionEntries);

$filterBuilding = isset($_GET['building']) && is_string($_GET['building']) ? trim($_GET['building']) : '';
$filterPeriodKey = isset($_GET['period']) && is_string($_GET['period']) ? trim($_GET['period']) : '';

$filteredGroups = [];
foreach ($periodGroups as $group) {
    if ($filterBuilding !== '' && $filterBuilding !== 'all') {
        $bid = $group['buildingId'] ?? null;
        if ($bid === null || (string) $bid !== $filterBuilding) {
            continue;
        }
    }
    $filteredGroups[] = $group;
}
if (count($filteredGroups) === 0) {
    $filteredGroups = $periodGroups;
}

$activeGroup = null;
if ($filterPeriodKey !== '') {
    foreach ($filteredGroups as $group) {
        if ((string) ($group['key'] ?? '') === $filterPeriodKey) {
            $activeGroup = $group;
            break;
        }
    }
}
if ($activeGroup === null && count($filteredGroups) > 0) {
    $activeGroup = $filteredGroups[0];
}

$activePhotos = is_array($activeGroup) && isset($activeGroup['photos']) && is_array($activeGroup['photos'])
    ? $activeGroup['photos']
    : [];
$activeVideos = is_array($activeGroup) && isset($activeGroup['videos']) && is_array($activeGroup['videos'])
    ? $activeGroup['videos']
    : [];

$pageTitle = site_format_page_title('Ход строительства — ' . $pageHeading);
$currentNav = 'catalog';

require __DIR__ . '/../../../includes/header.php';

if ($error) {
    ?>
    <main class="page-main page-main--inner" id="main">
        <div class="container">
            <p class="page-main__lead"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </main>
    <?php
    require __DIR__ . '/../../../includes/footer.php';
    return;
}
?>

<main class="page-main page-main--inner listing-construction-page" id="main">
    <div class="container">
        <nav class="listing__crumbs" aria-label="Хлебные крошки">
            <a class="listing__crumb" href="/catalog/">Каталог</a>
            <span class="listing__crumb-sep" aria-hidden="true">/</span>
            <a class="listing__crumb" href="/catalog/object/?id=<?php echo rawurlencode($id); ?>"><?php echo htmlspecialchars($headingTitle, ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="listing__crumb-sep" aria-hidden="true">/</span>
            <span class="listing__crumb listing__crumb--current">Ход строительства</span>
        </nav>

        <header class="listing-construction__head">
            <h1 class="listing-construction__title">Ход строительства</h1>
            <p class="listing-construction__complex"><?php echo htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if ($lastUpdate !== null) { ?>
                <p class="listing-construction__updated">Актуально на <?php echo htmlspecialchars($lastUpdate, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>
        </header>

        <?php if (count($periodGroups) === 0) { ?>
            <p class="listing-construction__empty">Фото и видео хода строительства пока не добавлены.</p>
        <?php } else { ?>
            <form class="listing-construction__filters" method="get" action="">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if (count($buildings) > 1) { ?>
                    <label class="listing-construction__filter">
                        <span class="listing-construction__filter-label">Корпус</span>
                        <select class="listing-construction__select" name="building" onchange="this.form.submit()">
                            <option value="all"<?php echo $filterBuilding === '' || $filterBuilding === 'all' ? ' selected' : ''; ?>>Все корпуса</option>
                            <?php foreach ($buildings as $building) {
                                $bid = $building['id'] ?? null;
                                $bval = $bid !== null ? (string) $bid : '';
                                ?>
                                <option value="<?php echo htmlspecialchars($bval, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filterBuilding === $bval ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $building['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </label>
                <?php } ?>
                <label class="listing-construction__filter">
                    <span class="listing-construction__filter-label">Период</span>
                    <select class="listing-construction__select" name="period" onchange="this.form.submit()">
                        <?php foreach ($filteredGroups as $group) {
                            $periodValue = (string) ($group['key'] ?? '');
                            $selected = is_array($activeGroup)
                                && (string) ($activeGroup['key'] ?? '') === $periodValue;
                            ?>
                            <option
                                value="<?php echo htmlspecialchars($periodValue, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $selected ? 'selected' : ''; ?>
                            ><?php echo htmlspecialchars((string) ($group['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                </label>
            </form>

            <?php if (is_array($activeGroup) && !empty($activeGroup['label'])) { ?>
                <h2 class="listing-construction__period-title"><?php echo htmlspecialchars((string) $activeGroup['label'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php } ?>

            <?php if (count($activeVideos) > 0) { ?>
                <div class="listing-construction__videos">
                    <?php foreach ($activeVideos as $videoUrl) { ?>
                        <div class="listing-construction__video-wrap">
                            <video class="listing-construction__video" controls preload="metadata" src="<?php echo htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8'); ?>"></video>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if (count($activePhotos) > 0) { ?>
                <div class="listing-construction__grid">
                    <?php foreach ($activePhotos as $photoUrl) { ?>
                        <a
                            class="listing-construction__photo"
                            href="<?php echo htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <img
                                class="listing-construction__photo-img"
                                src="<?php echo htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="Фото хода строительства"
                                loading="lazy"
                                decoding="async"
                                referrerpolicy="no-referrer"
                            >
                        </a>
                    <?php } ?>
                </div>
            <?php } elseif (count($activeVideos) === 0) { ?>
                <p class="listing-construction__empty">Для выбранного периода нет материалов.</p>
            <?php } ?>
        <?php } ?>

        <p class="listing-construction__back">
            <a class="listing-object__section-link" href="/catalog/object/?id=<?php echo rawurlencode($id); ?>">← К карточке объекта</a>
        </p>
    </div>
</main>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>
