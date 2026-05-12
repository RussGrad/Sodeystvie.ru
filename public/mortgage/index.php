<?php

declare(strict_types=1);

$pageTitle = 'Ипотека — Содействие';
$currentNav = 'mortgage';

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner" id="main">
    <div class="container">
        <h1 class="page-main__heading">Ипотека</h1>
        <p class="page-main__lead">Рассчитайте примерный платёж и оставьте заявку — мы подберём программу под ваш бюджет.</p>

        <section class="mortgage" aria-labelledby="mortgage-calculator-title" id="calculator">
            <div class="mortgage__grid">
                <div class="mortgage__card">
                    <h2 class="mortgage__title" id="mortgage-calculator-title">Ипотечный калькулятор</h2>
                    <form class="mortgage__form" id="mortgage-calc-form" novalidate>
                        <div class="mortgage__fields">
                            <label class="mortgage__field">
                                <span class="mortgage__label">Стоимость недвижимости</span>
                                <input class="mortgage__input" type="number" inputmode="numeric" name="price" min="0" step="10000" value="5000000" required>
                            </label>
                            <label class="mortgage__field">
                                <span class="mortgage__label">Первоначальный взнос</span>
                                <input class="mortgage__input" type="number" inputmode="numeric" name="down" min="0" step="10000" value="1000000" required>
                            </label>
                            <label class="mortgage__field">
                                <span class="mortgage__label">Срок, лет</span>
                                <input class="mortgage__input" type="number" inputmode="numeric" name="years" min="1" max="40" step="1" value="20" required>
                            </label>
                            <label class="mortgage__field">
                                <span class="mortgage__label">Ставка, % годовых</span>
                                <input class="mortgage__input" type="number" inputmode="decimal" name="rate" min="0" max="40" step="0.1" value="12.5" required>
                            </label>
                        </div>
                        <p class="mortgage__note">Результат ориентировочный. Точные условия зависят от банка, программы и вашего профиля.</p>
                    </form>
                </div>

                <div class="mortgage__card mortgage__card--result" aria-live="polite">
                    <h2 class="mortgage__title">Результат</h2>
                    <dl class="mortgage__result">
                        <div class="mortgage__result-row">
                            <dt>Сумма кредита</dt>
                            <dd id="mortgage-loan">—</dd>
                        </div>
                        <div class="mortgage__result-row">
                            <dt>Ежемесячный платёж</dt>
                            <dd class="mortgage__accent" id="mortgage-monthly">—</dd>
                        </div>
                        <div class="mortgage__result-row">
                            <dt>Переплата</dt>
                            <dd id="mortgage-overpay">—</dd>
                        </div>
                        <div class="mortgage__result-row">
                            <dt>Итого к оплате</dt>
                            <dd id="mortgage-total">—</dd>
                        </div>
                    </dl>
                    <a class="mortgage__cta" href="#apply">Оставить заявку</a>
                </div>
            </div>
        </section>

        <section class="mortgage-apply" aria-labelledby="mortgage-apply-title" id="apply">
            <h2 class="mortgage-apply__title" id="mortgage-apply-title">Заявка на ипотеку</h2>
            <p class="mortgage-apply__lead">Оставьте контакты — перезвоним и уточним детали.</p>
            <button type="button" class="mortgage-apply__btn" data-lead-open aria-haspopup="dialog" aria-controls="lead-modal">Оставить заявку</button>
        </section>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';

