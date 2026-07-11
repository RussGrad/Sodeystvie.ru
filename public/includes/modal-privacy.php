<?php

declare(strict_types=1);

/**
 * Модальное окно политики конфиденциальности.
 */

?>
<div
    id="privacy-modal"
    class="modal-privacy"
    aria-hidden="true"
    inert
>
    <div class="modal-privacy__backdrop" data-privacy-modal-close tabindex="-1"></div>
    <div
        class="modal-privacy__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="privacy-modal-title"
        tabindex="-1"
    >
        <div class="modal-privacy__head">
            <h2 class="modal-privacy__title" id="privacy-modal-title">Политика конфиденциальности</h2>
            <button
                type="button"
                class="modal-privacy__close"
                data-privacy-modal-close
                aria-label="Закрыть окно"
            >&times;</button>
        </div>
        <div class="modal-privacy__body">
            <?php site_privacy_policy_render('legal-text legal-text--modal'); ?>
        </div>
    </div>
</div>
