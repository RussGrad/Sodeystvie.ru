<?php

declare(strict_types=1);

?>
<section class="mortgage-quiz" aria-labelledby="mortgage-quiz-title">
    <div class="container">
        <div class="mortgage-quiz__card">
            <header class="mortgage-quiz__head">
                <h2 class="mortgage-quiz__title" id="mortgage-quiz-title">Подбор ипотеки за 1 минуту</h2>
                <p class="mortgage-quiz__lead">Ответьте на 4 вопроса — подготовим расчёт одобрения в банках Иркутска.</p>
            </header>

            <form class="mortgage-quiz__form" id="mortgage-quiz-form" novalidate>
                <div class="mortgage-quiz__progress" aria-hidden="true">
                    <span class="mortgage-quiz__progress-bar" id="mortgage-quiz-progress"></span>
                </div>

                <fieldset class="mortgage-quiz__step is-active" data-quiz-step="1">
                    <legend class="mortgage-quiz__question">Какая недвижимость вас интересует?</legend>
                    <div class="mortgage-quiz__options">
                        <label class="mortgage-quiz__option"><input type="radio" name="property" value="new" required> Новостройка</label>
                        <label class="mortgage-quiz__option"><input type="radio" name="property" value="resale"> Вторичка</label>
                        <label class="mortgage-quiz__option"><input type="radio" name="property" value="house"> Дом / участок</label>
                    </div>
                </fieldset>

                <fieldset class="mortgage-quiz__step" data-quiz-step="2" hidden>
                    <legend class="mortgage-quiz__question">Какой первоначальный взнос планируете?</legend>
                    <div class="mortgage-quiz__options">
                        <label class="mortgage-quiz__option"><input type="radio" name="down" value="0-20" required> До 20%</label>
                        <label class="mortgage-quiz__option"><input type="radio" name="down" value="20-30"> 20–30%</label>
                        <label class="mortgage-quiz__option"><input type="radio" name="down" value="30+"> Более 30%</label>
                    </div>
                </fieldset>

                <fieldset class="mortgage-quiz__step" data-quiz-step="3" hidden>
                    <legend class="mortgage-quiz__question">Планируете использовать материнский капитал?</legend>
                    <div class="mortgage-quiz__options">
                        <label class="mortgage-quiz__option"><input type="radio" name="matcap" value="yes" required> Да</label>
                        <label class="mortgage-quiz__option"><input type="radio" name="matcap" value="no"> Нет</label>
                        <label class="mortgage-quiz__option"><input type="radio" name="matcap" value="maybe"> Пока не знаю</label>
                    </div>
                </fieldset>

                <fieldset class="mortgage-quiz__step" data-quiz-step="4" hidden>
                    <legend class="mortgage-quiz__question">Куда отправить расчёт?</legend>
                    <div class="mortgage-quiz__fields">
                        <label class="mortgage-quiz__field">
                            <span class="mortgage-quiz__label">Имя</span>
                            <input class="mortgage-quiz__input" type="text" name="name" id="mortgage-quiz-name" autocomplete="name" maxlength="120" required>
                        </label>
                        <label class="mortgage-quiz__field">
                            <span class="mortgage-quiz__label">Телефон</span>
                            <input class="mortgage-quiz__input" type="tel" name="phone" id="mortgage-quiz-phone" autocomplete="tel" inputmode="tel" required>
                        </label>
                    </div>
                    <p class="mortgage-quiz__note">Свяжемся и пришлём варианты одобрения в банках-партнёрах Иркутска.</p>
                </fieldset>

                <div class="mortgage-quiz__actions">
                    <button type="button" class="mortgage-quiz__btn mortgage-quiz__btn--back" id="mortgage-quiz-back" hidden>Назад</button>
                    <button type="button" class="mortgage-quiz__btn mortgage-quiz__btn--next" id="mortgage-quiz-next">Далее</button>
                    <button type="submit" class="mortgage-quiz__btn mortgage-quiz__btn--submit" id="mortgage-quiz-submit" hidden>Получить расчёт</button>
                </div>

                <p class="mortgage-quiz__error" id="mortgage-quiz-error" hidden role="alert"></p>
                <p class="mortgage-quiz__success" id="mortgage-quiz-success" hidden role="status">Спасибо! Мы свяжемся с вами для расчёта ипотеки.</p>
            </form>

            <p class="mortgage-quiz__calc-link">
                Нужен точный платёж?
                <a href="/mortgage/">Открыть ипотечный калькулятор</a>
            </p>
        </div>
    </div>
</section>
