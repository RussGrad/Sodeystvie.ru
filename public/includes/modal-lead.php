<?php

declare(strict_types=1);

/**
 * Модальное окно заявки: имя и телефон. Открывается с кнопки «Оставить заявку» в шапке.
 */

?>
<div
    id="lead-modal"
    class="modal-lead"
    aria-hidden="true"
    inert
>
    <div class="modal-lead__backdrop" data-lead-modal-close tabindex="-1"></div>
    <div
        class="modal-lead__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="lead-modal-title"
        tabindex="-1"
    >
        <div class="modal-lead__head">
            <h2 class="modal-lead__title" id="lead-modal-title">Оставить заявку</h2>
            <button
                type="button"
                class="modal-lead__close"
                data-lead-modal-close
                aria-label="Закрыть окно"
            >&times;</button>
        </div>
        <form class="modal-lead__form" id="lead-form" novalidate>
            <div class="modal-lead__hp visually-hidden" aria-hidden="true">
                <label for="lead-company">Компания</label>
                <input type="text" id="lead-company" name="company" tabindex="-1" autocomplete="off">
            </div>
            <div class="modal-lead__field">
                <label class="modal-lead__label" for="lead-name">Имя</label>
                <input
                    class="modal-lead__input"
                    id="lead-name"
                    name="name"
                    type="text"
                    autocomplete="name"
                    required
                    maxlength="120"
                >
            </div>
            <div class="modal-lead__field">
                <label class="modal-lead__label" for="lead-phone">Телефон</label>
                <input
                    class="modal-lead__input"
                    id="lead-phone"
                    name="phone"
                    type="tel"
                    autocomplete="tel"
                    inputmode="numeric"
                    placeholder="+7 (999) 999-99-99"
                    required
                    maxlength="18"
                    aria-describedby="lead-phone-hint"
                >
                <span class="modal-lead__hint visually-hidden" id="lead-phone-hint">Формат: +7 и 10 цифр номера</span>
            </div>
            <p class="modal-lead__error" id="lead-form-error" hidden role="alert"></p>
            <p class="modal-lead__success" id="lead-form-success" hidden role="status" tabindex="-1">Спасибо! Мы свяжемся с вами.</p>
            <button type="submit" class="modal-lead__submit" id="lead-form-submit">Отправить</button>
        </form>
    </div>
</div>
