<?php

declare(strict_types=1);

/**
 * Первый экран: слайдер фона (CRM или запасной кадр), заголовок, вкладки, панель фильтров.
 */

require_once __DIR__ . '/crm-listing-helpers.php';

$heroTabsMain = [
    'buy' => 'Купить',
    'sell' => 'Продать',
    'rent_in' => 'Снять',
    'rent_out' => 'Сдать',
    'new' => 'Новостройки',
];

$heroSlides = site_hero_slides_cached(5);
$heroSlides = array_values(array_filter(
    $heroSlides,
    static function (array $slide): bool {
        return trim((string) ($slide['src'] ?? '')) !== '';
    }
));

$heroSlideCount = count($heroSlides);
$heroSliderEnabled = $heroSlideCount > 1;

/** Динамика цен на жильё в России, ₽/м² (источник — пользовательские данные). */
$heroGrowthBars = [
    ['year' => '2022', 'period' => 'Янв. 2022', 'newbuild' => 141150, 'resale' => 107712],
    ['year' => '2023', 'period' => 'Янв. 2023', 'newbuild' => 164340, 'resale' => 117350],
    ['year' => '2024', 'period' => 'Янв. 2024', 'newbuild' => 184761, 'resale' => 125137],
    ['year' => '2025', 'period' => 'Янв. 2025', 'newbuild' => 191314, 'resale' => 128877],
    ['year' => '2026', 'period' => 'Сер. 2026', 'newbuild' => 218800, 'resale' => 141500, 'projected' => true],
];
$heroGrowthMax = 1;
$heroGrowthMin = PHP_INT_MAX;
foreach ($heroGrowthBars as $bar) {
    $v = (int) ($bar['resale'] ?? 0);
    $heroGrowthMax = max($heroGrowthMax, $v);
    $heroGrowthMin = min($heroGrowthMin, $v);
}
if ($heroGrowthMin === PHP_INT_MAX) {
    $heroGrowthMin = 0;
}
$heroGrowthForecast = $heroGrowthBars[count($heroGrowthBars) - 1];
$heroGrowthSpan = max(1, $heroGrowthMax - $heroGrowthMin);
$heroGrowthBarCount = count($heroGrowthBars);

?>
<section class="hero" aria-labelledby="hero-title">
    <div class="hero__media" data-hero-slider aria-hidden="true">
        <?php foreach ($heroSlides as $i => $slide) {
            $src = trim((string) ($slide['src'] ?? ''));
            $alt = trim((string) ($slide['alt'] ?? ''));
            $active = $i === 0 ? ' is-active' : '';
            ?>
            <?php
            $heroImgClass = 'hero__slide' . $active;
            $heroImgExtra = 'width="1920" height="1080" decoding="async" '
                . ($i === 0 ? 'fetchpriority="high"' : 'loading="lazy"');
            if (str_starts_with($src, '/assets/')) {
                echo site_render_static_picture($src, $alt, $heroImgClass, $heroImgExtra);
            } else {
                ?>
            <img
                class="<?php echo htmlspecialchars($heroImgClass, ENT_QUOTES, 'UTF-8'); ?>"
                src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>"
                width="1920"
                height="1080"
                decoding="async"
                <?php echo $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
                referrerpolicy="no-referrer"
            >
                <?php
            }
            ?>
        <?php } ?>
    </div>

    <aside
        class="hero__growth"
        aria-label="Динамика цен на жильё в России"
    >
        <div class="hero-growth">
            <div class="hero-growth__card">
                <p class="hero-growth__kicker">Динамика цен на жильё в России</p>
                <strong class="hero-growth__headline">
                    <?php echo number_format((int) $heroGrowthForecast['resale'], 0, '', ' '); ?> ₽
                </strong>
                <p class="hero-growth__caption">
                    вторичный рынок за м² · прогноз <?php echo htmlspecialchars((string) $heroGrowthForecast['period'], ENT_QUOTES, 'UTF-8'); ?>
                    · новостройки <?php echo number_format((int) $heroGrowthForecast['newbuild'], 0, '', ' '); ?> ₽
                </p>
            </div>
            <div class="hero-growth__chart">
                <div
                    class="hero-growth__bars"
                    style="--hero-growth-cols: <?php echo (int) $heroGrowthBarCount; ?>"
                >
                    <?php foreach ($heroGrowthBars as $bar) {
                        $year = (string) ($bar['year'] ?? '');
                        $value = (int) ($bar['resale'] ?? 0);
                        $projected = !empty($bar['projected']);
                        $heightPct = max(14.0, min(100.0, round((($value - $heroGrowthMin) / $heroGrowthSpan) * 100, 1)));
                        $colClass = 'hero-growth__col' . ($projected ? ' hero-growth__col--projected' : '');
                        $prevValue = $projected ? (int) ($heroGrowthBars[count($heroGrowthBars) - 2]['resale'] ?? 1) : 0;
                        ?>
                    <div class="<?php echo htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="hero-growth__stack">
                            <div
                                class="hero-growth__rise"
                                style="height: <?php echo htmlspecialchars((string) $heightPct, ENT_QUOTES, 'UTF-8'); ?>%"
                            >
                                <?php if ($projected) { ?>
                                <div class="hero-growth__callout">
                                    <span class="hero-growth__callout-icon" aria-hidden="true">📈</span>
                                    <span>+<?php echo (int) round((($value / max(1, $prevValue)) - 1) * 100); ?>%</span>
                                </div>
                                <?php } ?>
                                <span class="hero-growth__value"><?php echo number_format($value, 0, '', ' '); ?></span>
                                <div class="hero-growth__bar"></div>
                            </div>
                        </div>
                        <span class="hero-growth__year"><?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </aside>

    <div class="container hero__layout">
        <p class="hero__eyebrow"><?php echo htmlspecialchars(site_brand_full(), ENT_QUOTES, 'UTF-8'); ?></p>
        <h1 class="hero__title" id="hero-title"><?php echo htmlspecialchars(site_hero_headline(), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="hero__lead"><?php echo htmlspecialchars(site_slogan_hero(), ENT_QUOTES, 'UTF-8'); ?></p>

        <div class="hero__search-wrap">
            <div class="hero__tabbar" role="tablist" aria-label="Тип сделки">
                <?php
                $first = true;
                foreach ($heroTabsMain as $id => $label) {
                    $selected = $first ? 'true' : 'false';
                    $activeClass = $first ? ' hero__tab--active' : '';
                    $first = false;
                    ?>
                    <button
                        type="button"
                        class="hero__tab<?php echo $activeClass; ?>"
                        role="tab"
                        aria-selected="<?php echo $selected; ?>"
                        data-deal="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"
                        id="hero-tab-<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"
                    ><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></button>
                <?php } ?>
                <button
                    type="button"
                    class="hero__tab hero__tab--abroad"
                    role="tab"
                    aria-selected="false"
                    data-deal="abroad"
                    id="hero-tab-abroad"
                >Зарубежная</button>
            </div>

            <div class="hero__search-card">
                <div class="hero__panel hero__panel--catalog" id="hero-panel-catalog" data-hero-panel="catalog">
                <form class="hero__form" action="/catalog/" method="get" id="hero-search-form">
                    <input type="hidden" name="deal" id="hero-deal" value="buy">

                    <div class="hero__filters" role="group" aria-label="Параметры поиска">
                        <div class="hero__filter">
                            <span class="hero__filter-name visually-hidden" id="hero-lbl-type">Квартиру</span>
                            <label class="hero__filter-field">
                                <span class="hero__filter-hint">Выберите недвижимость</span>
                                <select class="hero__select" name="type" aria-labelledby="hero-lbl-type">
                                    <option value="">Тип объекта</option>
                                    <option value="flat">Квартира</option>
                                    <option value="house">Дом</option>
                                    <option value="land">Участок</option>
                                    <option value="commercial">Коммерция</option>
                                </select>
                            </label>
                        </div>
                        <div class="hero__filter">
                            <span class="hero__filter-name visually-hidden" id="hero-lbl-rooms">Комнат</span>
                            <label class="hero__filter-field">
                                <span class="hero__filter-hint">Выберите кол-во комнат</span>
                                <select class="hero__select" name="rooms" aria-labelledby="hero-lbl-rooms">
                                    <option value="">Любое</option>
                                    <option value="studio">Студия</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4plus">4+</option>
                                </select>
                            </label>
                        </div>
                        <div class="hero__filter">
                            <span class="hero__filter-name visually-hidden" id="hero-lbl-price">Цена</span>
                            <label class="hero__filter-field">
                                <span class="hero__filter-hint">Выберите диапазон</span>
                                <select class="hero__select" name="price" aria-labelledby="hero-lbl-price">
                                    <option value="">Любая</option>
                                    <option value="0-5">до 5 млн</option>
                                    <option value="5-10">5–10 млн</option>
                                    <option value="10-20">10–20 млн</option>
                                    <option value="20+">от 20 млн</option>
                                </select>
                            </label>
                        </div>
                        <div class="hero__filter">
                            <span class="hero__filter-name visually-hidden" id="hero-lbl-region">Регион</span>
                            <label class="hero__filter-field">
                                <span class="hero__filter-hint">Выберите регион</span>
                                <select class="hero__select" name="region" aria-labelledby="hero-lbl-region">
                                    <option value="">Регион</option>
                                    <option value="irkutsk" selected>Иркутск</option>
                                    <option value="angarsk">Ангарск</option>
                                    <option value="moscow">Москва</option>
                                    <option value="mo">Московская область</option>
                                </select>
                            </label>
                        </div>
                    </div>
                </form>
                </div>

                <div class="hero__panel hero__panel--lead" id="hero-panel-lead" data-hero-panel="lead" hidden>
                    <p class="hero__lead-title" id="hero-lead-title">Хотите продать квартиру в Иркутске?</p>
                    <p class="hero__lead-text" id="hero-lead-text">Оставьте адрес — проведём бесплатную оценку стоимости за 30 минут.</p>
                    <label class="hero__lead-field">
                        <span class="hero__filter-hint" id="hero-lead-field-label">Адрес или район объекта</span>
                        <input
                            type="text"
                            class="hero__lead-input"
                            id="hero-lead-address"
                            name="address"
                            maxlength="200"
                            placeholder="Например: ул. Карла Маркса, 25"
                            autocomplete="street-address"
                        >
                    </label>
                    <button
                        type="button"
                        class="hero__btn hero__btn--find hero__lead-submit"
                        id="hero-lead-submit"
                        data-lead-open
                        data-lead-topic="sell-evaluation"
                    >Получить оценку</button>
                </div>
            </div>

            <div class="hero__actions">
                <button id="hero-primary-action" type="submit" class="hero__btn hero__btn--find" form="hero-search-form">Найти</button>
                <a class="hero__btn hero__btn--map" id="hero-map-link" href="/catalog/map/">На карте</a>
            </div>
        </div>
    </div>

    <?php if ($heroSliderEnabled) { ?>
    <div class="hero__slider-ui" data-hero-slider-ui>
            <div class="hero__slider-nav">
                <button type="button" class="hero__slider-arrow" data-hero-prev aria-label="Предыдущее фото">&#8249;</button>
                <button type="button" class="hero__slider-arrow" data-hero-next aria-label="Следующее фото">&#8250;</button>
            </div>
            <div class="hero__slider-dots" role="tablist" aria-label="Слайды фона">
                <?php foreach ($heroSlides as $i => $slide) {
                    $dotActive = $i === 0 ? ' hero__slider-dot--active' : '';
                    ?>
                    <button
                        type="button"
                        class="hero__slider-dot<?php echo $dotActive; ?>"
                        data-hero-dot="<?php echo (int) $i; ?>"
                        role="tab"
                        aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
                        aria-label="Слайд <?php echo (int) ($i + 1); ?>"
                    ></button>
                <?php } ?>
            </div>
    </div>
    <?php } ?>

    <button
        type="button"
        class="hero__chat"
        data-site-chat-open
        aria-label="Открыть онлайн-чат"
        aria-controls="site-chat"
        aria-haspopup="dialog"
    >
        <svg class="hero__chat-icon" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm0 14H6l-2 2V4h16v12z"/>
        </svg>
    </button>
</section>
