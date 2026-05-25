<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

$pageTitle = site_format_page_title('Контакты');
$currentNav = 'contacts';

$office = site_office_location();
$yandexMapsKey = site_yandex_maps_api_key();
$mapsExternalUrl = site_yandex_maps_external_url($office);
$telegramUrl = site_telegram_url();
$whatsappUrl = site_whatsapp_url();
$maxUrl = site_max_url();

$officeJson = json_encode([
    'lat' => $office['lat'],
    'lng' => $office['lng'],
    'zoom' => $office['zoom'],
    'address' => $office['address'],
    'title' => $office['title'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

$contactsMapJsVersion = (string) (@filemtime(__DIR__ . '/../js/contacts-map.js') ?: time());
$contactsFormJsVersion = (string) (@filemtime(__DIR__ . '/../js/contacts-form.js') ?: time());
$recaptchaSiteKey = site_recaptcha_site_key();

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner page-main--contacts" id="main">
    <div class="container">
        <h1 class="page-main__heading">Контакты</h1>
        <p class="page-main__lead">Свяжитесь с нами удобным способом или приезжайте в офис.</p>

        <div class="contacts-page">
            <aside class="contacts-page__panel contacts-page__panel--info" aria-label="Контактная информация">
                <h2 class="contacts-page__panel-title">Контактные данные</h2>
                <dl class="contacts-page__details">
                    <div class="contacts-page__row">
                        <dt class="contacts-page__label">Телефон</dt>
                        <dd class="contacts-page__value">
                            <a href="tel:<?php echo htmlspecialchars(SITE_PHONE_TEL, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(SITE_PHONE_DISPLAY, ENT_QUOTES, 'UTF-8'); ?></a>
                        </dd>
                    </div>
                    <div class="contacts-page__row">
                        <dt class="contacts-page__label">Email</dt>
                        <dd class="contacts-page__value">
                            <a href="mailto:<?php echo htmlspecialchars(SITE_EMAIL, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(SITE_EMAIL, ENT_QUOTES, 'UTF-8'); ?></a>
                        </dd>
                    </div>
                    <div class="contacts-page__row">
                        <dt class="contacts-page__label">Адрес</dt>
                        <dd class="contacts-page__value"><?php echo htmlspecialchars(SITE_ADDRESS, ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="contacts-page__row">
                        <dt class="contacts-page__label">Режим работы</dt>
                        <dd class="contacts-page__value"><?php echo htmlspecialchars(SITE_WORK_HOURS, ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <?php if ($telegramUrl !== null || $whatsappUrl !== null || $maxUrl !== '') { ?>
                        <div class="contacts-page__row">
                            <dt class="contacts-page__label">Мессенджеры</dt>
                            <dd class="contacts-page__value">
                                <ul class="contacts-page__messengers">
                                    <?php if ($telegramUrl !== null) { ?>
                                        <li><a href="<?php echo htmlspecialchars($telegramUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Telegram</a></li>
                                    <?php } ?>
                                    <?php if ($whatsappUrl !== null) { ?>
                                        <li><a href="<?php echo htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a></li>
                                    <?php } ?>
                                    <?php if ($maxUrl !== '') { ?>
                                        <li><a href="<?php echo htmlspecialchars($maxUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">MAX</a></li>
                                    <?php } ?>
                                </ul>
                            </dd>
                        </div>
                    <?php } ?>
                </dl>
            </aside>

            <section class="contacts-page__panel contacts-page__panel--map" aria-labelledby="contacts-map-heading">
                <h2 class="contacts-page__panel-title" id="contacts-map-heading">Как нас найти</h2>
                <div class="contacts-page__map-body">
                    <div class="contacts-page__map-wrap">
                        <?php if ($yandexMapsKey === '') { ?>
                            <div class="contacts-page__map-message">
                                <p>Интерактивная карта подключается ключом <code>YANDEX_MAPS_API_KEY</code> в <code>.env</code> на хостинге.</p>
                                <p><a href="https://developer.tech.yandex.ru/">Получить ключ</a> → «JavaScript API».</p>
                            </div>
                        <?php } else { ?>
                            <div
                                class="contacts-page__map-canvas"
                                id="contacts-map"
                                data-office="<?php echo htmlspecialchars($officeJson, ENT_QUOTES, 'UTF-8'); ?>"
                                role="application"
                                aria-label="Карта: офис <?php echo htmlspecialchars(SITE_BRAND_FULL, ENT_QUOTES, 'UTF-8'); ?>"
                            ></div>
                            <script src="https://api-maps.yandex.ru/2.1/?apikey=<?php echo htmlspecialchars($yandexMapsKey, ENT_QUOTES, 'UTF-8'); ?>&amp;lang=ru_RU"></script>
                            <script src="/js/contacts-map.js?v=<?php echo htmlspecialchars($contactsMapJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
                        <?php } ?>
                    </div>
                    <a class="contacts-page__map-link" href="<?php echo htmlspecialchars($mapsExternalUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Открыть в Яндекс.Картах</a>
                </div>
            </section>

            <?php require __DIR__ . '/../includes/contacts-form.php'; ?>
        </div>
    </div>
</main>
<?php if ($recaptchaSiteKey !== '') { ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php } ?>
<script src="/js/contacts-form.js?v=<?php echo htmlspecialchars($contactsFormJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php
require __DIR__ . '/../includes/footer.php';
