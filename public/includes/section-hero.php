<?php

declare(strict_types=1);

/**
 * Первый экран: фоновое фото + оверлей, заголовок, вкладки 5 + «Зарубежная», белая панель фильтров.
 * Референс — только компоновка; фон: /assets/hero/hero-bg.jpg (заменяемый файл).
 */

$heroTabsMain = [
    'buy' => 'Купить',
    'sell' => 'Продать',
    'rent_in' => 'Снять',
    'rent_out' => 'Сдать',
    'new' => 'Новостройки',
];

?>
<section class="hero" aria-labelledby="hero-title">
    <div class="hero__media" aria-hidden="true"></div>

    <div class="container hero__layout">
        <h1 class="hero__title" id="hero-title">Поможем найти квартиру вашей мечты</h1>

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
                            <span class="hero__filter-name visually-hidden" id="hero-lbl-region">Ангарск</span>
                            <label class="hero__filter-field">
                                <span class="hero__filter-hint">Выберите регион</span>
                                <select class="hero__select" name="region" aria-labelledby="hero-lbl-region">
                                    <option value="">Регион</option>
                                    <option value="angarsk">Ангарск</option>
                                    <option value="moscow">Москва</option>
                                    <option value="mo">Московская область</option>
                                </select>
                            </label>
                        </div>
                    </div>
                </form>
            </div>

            <div class="hero__actions">
                <button id="hero-primary-action" type="submit" class="hero__btn hero__btn--find" form="hero-search-form">Найти</button>
                <a class="hero__btn hero__btn--map" href="/catalog/?view=map">На карте</a>
            </div>
        </div>

        <div class="hero__slider-ui" aria-hidden="true">
            <span class="hero__slider-nav">
                <span class="hero__slider-arrow">&#8249;</span>
                <span class="hero__slider-arrow">&#8250;</span>
            </span>
            <span class="hero__slider-dots">
                <span class="hero__slider-dot hero__slider-dot--active"></span>
                <span class="hero__slider-dot"></span>
                <span class="hero__slider-dot"></span>
            </span>
        </div>
    </div>

    <a class="hero__chat" href="/contacts/" aria-label="Связаться с нами">
        <svg class="hero__chat-icon" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm0 14H6l-2 2V4h16v12z"/>
        </svg>
    </a>
</section>
