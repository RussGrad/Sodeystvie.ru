<?php

declare(strict_types=1);

/**
 * Форма обратной связи на странице «Контакты».
 */

if (!function_exists('site_contacts_form_regions')) {
    /** @return array<string, string> value => label */
    function site_contacts_form_regions(): array
    {
        return [
            '' => 'Выбрать',
            'irkutsk' => 'Иркутск',
            'irkutsk-region' => 'Иркутская область',
            'angarsk' => 'Ангарск',
            'other' => 'Другой регион',
        ];
    }
}

if (!function_exists('site_contacts_form_subjects')) {
    /** @return array<string, string> value => label */
    function site_contacts_form_subjects(): array
    {
        return [
            'support' => 'Техподдержка',
            'consult' => 'Консультация по недвижимости',
            'buy-sell' => 'Покупка / продажа',
            'rent' => 'Аренда',
            'mortgage' => 'Ипотека',
            'other' => 'Другое',
        ];
    }
}

// API (lead-submit) подключает только хелперы — без HTML в JSON-ответе.
if (defined('SITE_CONTACTS_FORM_HELPERS_ONLY') && SITE_CONTACTS_FORM_HELPERS_ONLY) {
    return;
}

$recaptchaSiteKey = site_recaptcha_site_key();
$regions = site_contacts_form_regions();
$subjects = site_contacts_form_subjects();
$defaultSubject = 'support';

?>
<section class="contacts-page__panel contacts-page__panel--form" aria-labelledby="contacts-form-heading">
    <h2 class="contacts-page__panel-title" id="contacts-form-heading">Написать нам</h2>

    <form class="contacts-form" id="contacts-form" novalidate>
        <div class="contacts-form__hp visually-hidden" aria-hidden="true">
            <label for="contacts-company">Компания</label>
            <input type="text" id="contacts-company" name="company" tabindex="-1" autocomplete="off">
        </div>

        <div class="contacts-form__field">
            <label class="contacts-form__label" for="contacts-name">
                Ваше имя: <span class="contacts-form__req" aria-hidden="true">*</span>
            </label>
            <input
                class="contacts-form__input"
                type="text"
                id="contacts-name"
                name="name"
                required
                maxlength="120"
                autocomplete="name"
            >
        </div>

        <div class="contacts-form__field">
            <label class="contacts-form__label" for="contacts-email">
                Электронная почта: <span class="contacts-form__req" aria-hidden="true">*</span>
            </label>
            <input
                class="contacts-form__input"
                type="email"
                id="contacts-email"
                name="email"
                required
                maxlength="160"
                autocomplete="email"
                inputmode="email"
            >
        </div>

        <div class="contacts-form__field">
            <label class="contacts-form__label" for="contacts-phone">Телефон:</label>
            <input
                class="contacts-form__input"
                type="tel"
                id="contacts-phone"
                name="phone"
                autocomplete="tel"
                inputmode="tel"
                placeholder="+7 (___) ___-__-__"
            >
        </div>

        <div class="contacts-form__field">
            <label class="contacts-form__label" for="contacts-region">Регион:</label>
            <select class="contacts-form__input contacts-form__select" id="contacts-region" name="region">
                <?php foreach ($regions as $value => $label) { ?>
                    <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $value === '' ? ' selected' : ''; ?>>
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="contacts-form__field">
            <label class="contacts-form__label" for="contacts-subject">Тема:</label>
            <select class="contacts-form__input contacts-form__select" id="contacts-subject" name="subject">
                <?php foreach ($subjects as $value => $label) { ?>
                    <option
                        value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $value === $defaultSubject ? ' selected' : ''; ?>
                    ><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="contacts-form__field">
            <label class="contacts-form__label" for="contacts-message">Сообщение:</label>
            <textarea
                class="contacts-form__input contacts-form__textarea"
                id="contacts-message"
                name="message"
                rows="3"
                maxlength="2000"
            ></textarea>
        </div>

        <?php if ($recaptchaSiteKey !== '') { ?>
            <div class="contacts-form__field contacts-form__field--captcha">
                <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
            </div>
        <?php } ?>

        <p class="contacts-form__error" id="contacts-form-error" hidden role="alert"></p>
        <p class="contacts-form__success" id="contacts-form-success" hidden role="status" tabindex="-1">
            Спасибо! Сообщение отправлено — мы свяжемся с вами.
        </p>

        <button type="submit" class="contacts-form__submit" id="contacts-form-submit">Отправить</button>

        <p class="contacts-form__note">
            Поля со <span class="contacts-form__req" aria-hidden="true">*</span> обязательны.
            Отправляя форму, вы соглашаетесь с <a href="/privacy/">политикой конфиденциальности</a>.
        </p>
    </form>
</section>
