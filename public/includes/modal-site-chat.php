<?php

declare(strict_types=1);

$siteChatEnabled = site_public_site_api_key() !== '';
?>
<div
    id="site-chat"
    class="site-chat"
    aria-hidden="true"
    inert
    data-site-chat-root
    data-enabled="<?php echo $siteChatEnabled ? '1' : '0'; ?>"
>
    <button type="button" class="site-chat__backdrop" data-site-chat-close tabindex="-1" aria-label="Закрыть чат"></button>
    <div
        class="site-chat__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="site-chat-title"
        tabindex="-1"
    >
        <header class="site-chat__head">
            <div class="site-chat__head-text">
                <h2 class="site-chat__title" id="site-chat-title">Онлайн-консультация</h2>
                <p class="site-chat__subtitle"><?php echo htmlspecialchars(site_brand_full(), ENT_QUOTES, 'UTF-8'); ?> · ответим в рабочее время</p>
            </div>
            <button type="button" class="site-chat__close" data-site-chat-close aria-label="Закрыть чат">
                <span aria-hidden="true">&times;</span>
            </button>
        </header>

        <div class="site-chat__messages" id="site-chat-messages" aria-live="polite" aria-relevant="additions">
            <p class="site-chat__welcome">Здравствуйте! Напишите вопрос — сообщение поступит оператору в CRM, ответ придёт сюда.</p>
        </div>

        <form class="site-chat__composer" id="site-chat-form">
            <label class="visually-hidden" for="site-chat-input">Сообщение</label>
            <textarea
                class="site-chat__input"
                id="site-chat-input"
                name="body"
                rows="2"
                maxlength="2000"
                placeholder="Ваш вопрос…"
                required
            ></textarea>
            <button type="submit" class="site-chat__send" id="site-chat-send">Отправить</button>
        </form>

        <p class="site-chat__error" id="site-chat-error" hidden role="alert"></p>
    </div>
</div>

<button
    type="button"
    class="site-chat-fab"
    data-site-chat-open
    aria-label="Открыть онлайн-чат"
    aria-controls="site-chat"
    aria-haspopup="dialog"
    <?php echo $siteChatEnabled ? '' : 'hidden'; ?>
>
    <svg class="site-chat-fab__icon" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm0 14H6l-2 2V4h16v12z"/>
    </svg>
</button>
