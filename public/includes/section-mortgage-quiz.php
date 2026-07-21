<?php

declare(strict_types=1);

require_once __DIR__ . '/messenger-links.php';

$leadImage = site_home_lead_image_src();
$leadImagePath = site_home_lead_image_path();
$leadIsMagazine = site_home_lead_image_is_magazine();

?>
<section class="mortgage-quiz" aria-labelledby="mortgage-quiz-title">
    <div class="container">
        <div class="mortgage-quiz__card">
            <div class="mortgage-quiz__content">
                <header class="mortgage-quiz__head">
                    <p class="mortgage-quiz__eyebrow">Ипотечный центр «Содействие»</p>
                    <h2 class="mortgage-quiz__title" id="mortgage-quiz-title"<?php echo site_ve_attrs('home_lead_title', 'textarea', 'Заголовок ипотечного блока'); ?>>
                        <?php echo htmlspecialchars(site_home_lead_title(), ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <p class="mortgage-quiz__lead"<?php echo site_ve_attrs('home_lead_description', 'textarea', 'Описание ипотечного блока'); ?>>
                        <?php echo htmlspecialchars(site_home_lead_description(), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </header>

                <ul class="mortgage-quiz__benefits" aria-label="Преимущества консультации">
                    <li class="mortgage-quiz__benefit">Сравним условия банков</li>
                    <li class="mortgage-quiz__benefit">Учтём первоначальный взнос</li>
                    <li class="mortgage-quiz__benefit">Разберём использование маткапитала</li>
                </ul>

                <div class="mortgage-quiz__form-panel">
                    <div class="mortgage-quiz__form-intro">
                        <p class="mortgage-quiz__form-title">Получите персональный расчёт</p>
                        <p class="mortgage-quiz__form-copy">Это займёт около минуты</p>
                    </div>

                    <form class="mortgage-quiz__form" id="mortgage-quiz-form" novalidate>
                        <div class="mortgage-quiz__hp visually-hidden" aria-hidden="true">
                            <label for="mortgage-quiz-company">Компания</label>
                            <input type="text" id="mortgage-quiz-company" name="company" tabindex="-1" autocomplete="off">
                        </div>
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
                            <p class="mortgage-quiz__note">
                                Отправляя форму, вы соглашаетесь с
                                <a href="/privacy/" data-privacy-open>политикой конфиденциальности</a>.
                            </p>
                        </fieldset>

                        <div class="mortgage-quiz__actions">
                            <button type="button" class="mortgage-quiz__btn mortgage-quiz__btn--back" id="mortgage-quiz-back" hidden>Назад</button>
                            <button type="button" class="mortgage-quiz__btn mortgage-quiz__btn--next" id="mortgage-quiz-next">Далее</button>
                            <button type="submit" class="mortgage-quiz__btn mortgage-quiz__btn--submit" id="mortgage-quiz-submit" hidden><?php echo htmlspecialchars(site_home_lead_cta(), ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>

                        <p class="mortgage-quiz__error" id="mortgage-quiz-error" hidden role="alert"></p>
                        <p class="mortgage-quiz__success" id="mortgage-quiz-success" hidden role="status">Спасибо! Мы свяжемся с вами для расчёта ипотеки.</p>
                    </form>

                    <div class="mortgage-quiz__contact">
                        <p class="mortgage-quiz__contact-label">Или напишите специалисту напрямую</p>
                        <?php site_render_messenger_links(
                            'messenger-links mortgage-quiz__messengers',
                            'messenger-links__item',
                            true,
                        ); ?>
                    </div>
                </div>
            </div>

            <div class="mortgage-quiz__visual<?php echo $leadImage !== '' ? ' mortgage-quiz__visual--image' : ''; ?><?php echo $leadIsMagazine ? ' mortgage-quiz__visual--magazine' : ''; ?>">
                <span class="mortgage-quiz__orbit mortgage-quiz__orbit--large" aria-hidden="true"></span>
                <span class="mortgage-quiz__orbit mortgage-quiz__orbit--small" aria-hidden="true"></span>
                <div class="mortgage-quiz__publication-wrap">
                    <div class="mortgage-quiz__publication<?php echo $leadIsMagazine ? ' mortgage-quiz__publication--magazine' : ''; ?>">
                        <?php if ($leadImagePath !== '') {
                            echo site_render_static_picture(
                                $leadImagePath,
                                'Промоматериал ипотечного центра «Содействие»',
                                $leadIsMagazine ? 'mortgage-quiz__magazine-cover' : 'mortgage-quiz__photo',
                                'width="720" height="988"'
                            );
                        } else { ?>
                            <div class="mortgage-quiz__publication-fallback" aria-hidden="true">
                                <span class="mortgage-quiz__publication-brand">Содействие</span>
                                <span class="mortgage-quiz__publication-rule"></span>
                                <strong>Гид по ипотеке<br>и покупке жилья</strong>
                                <small>Практические решения для вашей ситуации</small>
                                <span class="mortgage-quiz__publication-year"><?php echo date('Y'); ?></span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="mortgage-quiz__visual-badge">
                    <span class="mortgage-quiz__visual-mark">С</span>
                    <span>Персональный расчёт<br>от специалиста</span>
                </div>
                <a class="mortgage-quiz__calc-link" href="/mortgage/">
                    Рассчитать примерный платёж
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </div>
</section>
