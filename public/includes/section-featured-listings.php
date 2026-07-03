<?php

declare(strict_types=1);

require_once __DIR__ . '/crm-listing-helpers.php';

$featuredData = site_crm_fetch_featured_listing_groups(4);
$groups = $featuredData['groups'];
$featuredError = $featuredData['error'];

$sections = [
    [
        'id' => 'featured-newbuild',
        'mod' => 'featured--newbuild',
        'title' => 'Лучшие новостройки Иркутска',
        'tagline' => 'для инвестирования',
        'lead' => 'Квартиры в новых ЖК с выгодными условиями покупки и потенциалом роста стоимости.',
        'catalogHref' => '/catalog/?type=newbuilding&region=irkutsk',
        'catalogLabel' => 'Все новостройки',
        'subsections' => [
            ['title' => null, 'catalogHref' => null, 'catalogLabel' => null, 'items' => $groups['newbuild']],
        ],
    ],
    [
        'id' => 'featured-resale',
        'mod' => '',
        'title' => 'Лучшие предложения по вторичному жилью',
        'tagline' => null,
        'lead' => 'Проверенные квартиры и дома — готовые к заселению и выгодные по цене.',
        'catalogHref' => '/catalog/?region=irkutsk',
        'catalogLabel' => 'Вторичка в каталоге',
        'subsections' => [
            [
                'title' => 'Продажа',
                'catalogHref' => '/catalog/?region=irkutsk&operation=sale',
                'catalogLabel' => 'Все на продажу',
                'items' => $groups['resale']['sale'],
            ],
            [
                'title' => 'Аренда',
                'catalogHref' => '/catalog/?region=irkutsk&operation=rent',
                'catalogLabel' => 'Все в аренду',
                'items' => $groups['resale']['rent'],
            ],
        ],
    ],
    [
        'id' => 'featured-commercial',
        'mod' => '',
        'title' => 'Лучшие предложения по коммерции',
        'tagline' => null,
        'lead' => 'Офисы, торговые и свободные помещения для бизнеса в Иркутске и области.',
        'catalogHref' => '/catalog/?type=commercial&region=irkutsk',
        'catalogLabel' => 'Коммерция в каталоге',
        'subsections' => [
            [
                'title' => 'Продажа',
                'catalogHref' => '/catalog/?type=commercial&region=irkutsk&operation=sale',
                'catalogLabel' => 'Все на продажу',
                'items' => $groups['commercial']['sale'],
            ],
            [
                'title' => 'Аренда',
                'catalogHref' => '/catalog/?type=commercial&region=irkutsk&operation=rent',
                'catalogLabel' => 'Все в аренду',
                'items' => $groups['commercial']['rent'],
            ],
        ],
    ],
];

$hasAnyItems = false;
foreach ($sections as $section) {
    foreach ($section['subsections'] as $subsection) {
        if (count($subsection['items']) > 0) {
            $hasAnyItems = true;
            break 2;
        }
    }
}

?>
<div class="featured-stack">
<?php if ($featuredError !== null && !$hasAnyItems) { ?>
    <section class="featured" aria-labelledby="featured-error-heading">
        <div class="container">
            <header class="featured__header">
                <div class="featured__intro">
                    <h2 class="featured__title" id="featured-error-heading">Лучшие предложения</h2>
                    <p class="featured__lead">Сейчас не удалось загрузить объекты из CRM. Смотрите <a href="/catalog/">каталог</a> или попробуйте позже.</p>
                </div>
                <a class="featured__to-catalog" href="/catalog/">Весь каталог</a>
            </header>
        </div>
    </section>
<?php } elseif (!$hasAnyItems) { ?>
    <section class="featured" aria-labelledby="featured-empty-heading">
        <div class="container">
            <header class="featured__header">
                <div class="featured__intro">
                    <h2 class="featured__title" id="featured-empty-heading">Лучшие предложения</h2>
                    <p class="featured__lead">Отметьте объекты в CRM («На главной сайта») по типу: новостройка, вторичка или коммерция.</p>
                </div>
                <a class="featured__to-catalog" href="/catalog/">Весь каталог</a>
            </header>
        </div>
    </section>
<?php } else {
    foreach ($sections as $section) {
        $sectionHasItems = false;
        foreach ($section['subsections'] as $subsection) {
            if (count($subsection['items']) > 0) {
                $sectionHasItems = true;
                break;
            }
        }
        if (!$sectionHasItems) {
            continue;
        }
        $sectionMod = trim((string) $section['mod']);
        ?>
    <section
        class="featured<?php echo $sectionMod !== '' ? ' ' . htmlspecialchars($sectionMod, ENT_QUOTES, 'UTF-8') : ''; ?>"
        aria-labelledby="<?php echo htmlspecialchars((string) $section['id'], ENT_QUOTES, 'UTF-8'); ?>"
    >
        <div class="container">
            <header class="featured__header">
                <div class="featured__intro">
                    <h2 class="featured__title" id="<?php echo htmlspecialchars((string) $section['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars((string) $section['title'], ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <?php if (!empty($section['tagline'])) { ?>
                        <p class="featured__tagline"><?php echo htmlspecialchars((string) $section['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
                    <p class="featured__lead"><?php echo htmlspecialchars((string) $section['lead'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <a class="featured__to-catalog" href="<?php echo htmlspecialchars((string) $section['catalogHref'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string) $section['catalogLabel'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </header>
            <?php foreach ($section['subsections'] as $subsection) {
                if (count($subsection['items']) === 0) {
                    continue;
                }
                $subsectionTitle = isset($subsection['title']) ? trim((string) $subsection['title']) : '';
                $subsectionCatalogHref = isset($subsection['catalogHref']) ? trim((string) $subsection['catalogHref']) : '';
                $subsectionCatalogLabel = isset($subsection['catalogLabel']) ? trim((string) $subsection['catalogLabel']) : '';
                ?>
            <div class="featured__subsection">
                <?php if ($subsectionTitle !== '') { ?>
                <div class="featured__subsection-head">
                    <h3 class="featured__subsection-title"><?php echo htmlspecialchars($subsectionTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php if ($subsectionCatalogHref !== '' && $subsectionCatalogLabel !== '') { ?>
                    <a class="featured__subsection-link" href="<?php echo htmlspecialchars($subsectionCatalogHref, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($subsectionCatalogLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <?php } ?>
                </div>
                <?php } ?>
                <ul class="featured__grid">
                    <?php foreach ($subsection['items'] as $row) {
                        if (is_array($row)) {
                            site_render_featured_listing_card($row);
                        }
                    } ?>
                </ul>
            </div>
                <?php
            } ?>
        </div>
    </section>
        <?php
    }
} ?>
</div>
