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
                                <span class="hero__filter-hint visually-hidden">Выберите недвижимость</span>
                                <select class="hero__select" name="type" aria-label="Выберите недвижимость" aria-labelledby="hero-lbl-type">
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
                                <span class="hero__filter-hint visually-hidden">Выберите кол-во комнат</span>
                                <select class="hero__select" name="rooms" aria-label="Выберите кол-во комнат" aria-labelledby="hero-lbl-rooms">
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
                                <span class="hero__filter-hint visually-hidden">Выберите диапазон</span>
                                <select class="hero__select" name="price" aria-label="Выберите диапазон" aria-labelledby="hero-lbl-price">
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
                                <span class="hero__filter-hint visually-hidden">Выберите регион</span>
                                <select class="hero__select" name="region" aria-label="Выберите регион" aria-labelledby="hero-lbl-region">
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
