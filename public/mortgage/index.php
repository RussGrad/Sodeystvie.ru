<?php

declare(strict_types=1);

$pageTitle = 'Ипотека в Иркутске — Содействие';
$currentNav = 'mortgage';

require_once __DIR__ . '/../includes/mortgage-banks.php';

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner page-main--mortgage" id="main">
    <div class="container">
        <header class="mortgage-page__head">
            <div class="mortgage-page__head-text">
                <h1 class="page-main__heading">Ипотека</h1>
                <p class="page-main__lead">Рассчитайте платёж по программам банков в Иркутске и Ангарске — подберём условия под ваш объект.</p>
            </div>
            <label class="mortgage-page__region">
                <span class="mortgage-page__region-label">Регион</span>
                <select class="mortgage-page__region-select" id="mortgage-region" name="region" aria-label="Регион расчёта">
                    <option value="irkutsk" selected>Иркутск</option>
                    <option value="angarsk">Ангарск</option>
                    <option value="moscow">Москва</option>
                    <option value="mo">Московская область</option>
                </select>
            </label>
        </header>

        <div class="mortgage-programs" role="tablist" aria-label="Ипотечные программы" id="programs">
            <button type="button" class="mortgage-programs__tab is-active" role="tab" aria-selected="true" data-program="base" id="2026">
                <span class="mortgage-programs__name">Для всех</span>
                <span class="mortgage-programs__rate">от 16,9%</span>
            </button>
            <button type="button" class="mortgage-programs__tab" role="tab" aria-selected="false" data-program="family" id="family">
                <span class="mortgage-programs__name">Семейная ипотека</span>
                <span class="mortgage-programs__rate">от 5,89%</span>
            </button>
            <button type="button" class="mortgage-programs__tab" role="tab" aria-selected="false" data-program="own" id="own-rate">
                <span class="mortgage-programs__name">Своя ставка</span>
                <span class="mortgage-programs__rate">от 11,9%</span>
            </button>
            <button type="button" class="mortgage-programs__tab" role="tab" aria-selected="false" data-program="rural" id="rural">
                <span class="mortgage-programs__name">Сельская ипотека</span>
                <span class="mortgage-programs__rate">от 3%</span>
            </button>
            <button type="button" class="mortgage-programs__tab" role="tab" aria-selected="false" data-program="it" id="it">
                <span class="mortgage-programs__name">Ипотека для IT</span>
                <span class="mortgage-programs__rate">от 5%</span>
            </button>
            <button type="button" class="mortgage-programs__tab" role="tab" aria-selected="false" data-program="military" id="military">
                <span class="mortgage-programs__name">Военная ипотека</span>
                <span class="mortgage-programs__rate">от 17,1%</span>
            </button>
        </div>

        <section class="mortgage-calc is-fast" aria-labelledby="mortgage-calculator-title" id="calculator">
            <div class="mortgage-calc__panel">
            <div class="mortgage-calc__toolbar">
                <h2 class="mortgage-calc__title" id="mortgage-calculator-title">Рассчитайте ипотеку</h2>
                <div class="mortgage-calc__mode" role="group" aria-label="Режим калькулятора">
                    <button type="button" class="mortgage-calc__mode-btn is-active" data-mode="fast" aria-pressed="true">Быстро</button>
                    <button type="button" class="mortgage-calc__mode-btn" data-mode="detail" aria-pressed="false">Подробно</button>
                </div>
            </div>
            <form class="mortgage-calc__form" id="mortgage-calc-form" novalidate>
                <div class="mortgage-calc__grid mortgage-calc__grid--detail" id="mortgage-detail-fields" hidden>
                    <label class="mortgage-calc__field">
                        <span class="mortgage-calc__label">Тип недвижимости</span>
                        <select class="mortgage-calc__select" name="propertyType" id="mortgage-property-type">
                            <option value="newbuild" selected>Новостройки</option>
                            <option value="resale">Вторичное жильё</option>
                            <option value="house">Дом</option>
                            <option value="plot">Участок</option>
                        </select>
                    </label>
                    <label class="mortgage-calc__field">
                        <span class="mortgage-calc__label">Социальная программа</span>
                        <select class="mortgage-calc__select" name="socialProgram" id="mortgage-social-program">
                            <option value="base" selected>Базовая программа</option>
                            <option value="family">Семейная</option>
                            <option value="rural">Сельская</option>
                            <option value="it">IT</option>
                        </select>
                    </label>
                </div>

                <div class="mortgage-calc__grid mortgage-calc__grid--main">
                    <label class="mortgage-calc__field">
                        <span class="mortgage-calc__label">Стоимость недвижимости</span>
                        <span class="mortgage-calc__input-wrap">
                            <input
                                class="mortgage-calc__input"
                                type="text"
                                name="price"
                                id="mortgage-price"
                                inputmode="numeric"
                                autocomplete="off"
                                value="5 000 000"
                                required
                            >
                            <span class="mortgage-calc__suffix">₽</span>
                        </span>
                    </label>

                    <div class="mortgage-calc__field mortgage-calc__field--down">
                        <span class="mortgage-calc__label">Первоначальный взнос</span>
                        <div class="mortgage-calc__down-row">
                            <span class="mortgage-calc__input-wrap mortgage-calc__input-wrap--grow">
                                <input
                                    class="mortgage-calc__input"
                                    type="text"
                                    name="downAmount"
                                    id="mortgage-down-amount"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    value="1 500 000"
                                >
                                <span class="mortgage-calc__suffix">₽</span>
                            </span>
                            <span class="mortgage-calc__input-wrap mortgage-calc__input-wrap--pct">
                                <input
                                    class="mortgage-calc__input mortgage-calc__input--pct"
                                    type="text"
                                    name="downPercent"
                                    id="mortgage-down-percent"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    value="30"
                                >
                                <span class="mortgage-calc__suffix">%</span>
                            </span>
                        </div>
                    </div>

                    <label class="mortgage-calc__field">
                        <span class="mortgage-calc__label">Срок кредита</span>
                        <span class="mortgage-calc__input-wrap">
                            <input
                                class="mortgage-calc__input"
                                type="text"
                                name="years"
                                id="mortgage-years"
                                inputmode="numeric"
                                autocomplete="off"
                                value="30"
                                required
                            >
                            <span class="mortgage-calc__suffix">лет</span>
                        </span>
                    </label>

                    <label class="mortgage-calc__field mortgage-calc__field--rate" id="mortgage-rate-field" hidden>
                        <span class="mortgage-calc__label">Ставка, % годовых</span>
                        <span class="mortgage-calc__input-wrap">
                            <input
                                class="mortgage-calc__input"
                                type="text"
                                name="rate"
                                id="mortgage-rate"
                                inputmode="decimal"
                                autocomplete="off"
                                value="16,9"
                            >
                            <span class="mortgage-calc__suffix">%</span>
                        </span>
                    </label>
                </div>

                <p class="mortgage-calc__note">Расчёт ориентировочный. Точные условия зависят от банка, программы и вашего профиля.</p>
            </form>

            <aside class="mortgage-summary" aria-live="polite">
                <div class="mortgage-summary__item">
                    <span class="mortgage-summary__label">Сумма кредита</span>
                    <strong class="mortgage-summary__value" id="mortgage-loan">—</strong>
                </div>
                <div class="mortgage-summary__item mortgage-summary__item--accent">
                    <span class="mortgage-summary__label">Ежемесячный платёж</span>
                    <strong class="mortgage-summary__value" id="mortgage-monthly">—</strong>
                </div>
                <div class="mortgage-summary__item">
                    <span class="mortgage-summary__label">Переплата</span>
                    <strong class="mortgage-summary__value" id="mortgage-overpay">—</strong>
                </div>
            </aside>

            <div class="mortgage-banks" aria-label="Предложения банков" data-banks-assets="/assets/banks/">
                <div class="mortgage-banks__track" id="mortgage-banks-track"></div>
            </div>
            <script type="application/json" id="mortgage-banks-data"><?php echo sodeystvie_mortgage_banks_json(); ?></script>

            <div class="mortgage-calc__actions">
                <button type="button" class="mortgage-calc__btn mortgage-calc__btn--primary" data-lead-open data-lead-topic="mortgage-apply">
                    Заполнить анкету
                </button>
                <button type="button" class="mortgage-calc__btn mortgage-calc__btn--secondary" data-lead-open data-lead-topic="mortgage-consult">
                    Получить консультацию
                </button>
            </div>
            </div>
        </section>

        <section class="mortgage-apply" aria-labelledby="mortgage-apply-title" id="apply">
            <h2 class="mortgage-apply__title" id="mortgage-apply-title">Заявка на ипотеку</h2>
            <p class="mortgage-apply__lead">Оставьте контакты — ипотечный специалист перезвонит, уточнит программу и поможет с одобрением.</p>
            <button type="button" class="mortgage-apply__btn" data-lead-open data-lead-topic="mortgage" aria-haspopup="dialog" aria-controls="lead-modal">
                Оставить заявку
            </button>
        </section>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';
