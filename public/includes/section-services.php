<?php

declare(strict_types=1);

/**
 * Секция «Услуги» на главной: сетка карточек.
 */

require_once __DIR__ . '/services-catalog.php';
require_once __DIR__ . '/services-icon.php';
require_once __DIR__ . '/visual-editor.php';

$servicesItems = sodeystvie_services_catalog();
$veOn = site_visual_editor_enabled();

?>
<section class="services" aria-labelledby="services-title">
    <div class="container">
        <div class="services__head">
            <h2 class="services__title" id="services-title">Услуги</h2>
            <div class="services__head-actions">
                <?php if ($veOn) { ?>
                    <button type="button" class="ve-add-btn" data-ve-add-service>Добавить услугу</button>
                <?php } ?>
                <a class="services__all-link" href="/services/">Все услуги</a>
            </div>
        </div>
        <ul class="services__grid">
            <?php foreach ($servicesItems as $item) {
                $sid = (string) ($item['id'] ?? '');
                ?>
                <li class="services__cell">
                    <article class="services__card<?php echo $veOn ? ' services__card--ve' : ''; ?>">
                        <?php if ($veOn) { ?>
                            <button
                                type="button"
                                class="ve-card-delete"
                                data-ve-delete-service="<?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?>"
                                title="Удалить услугу"
                                aria-label="Удалить услугу «<?php echo htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>»"
                            >×</button>
                        <?php } ?>
                        <?php sodeystvie_services_render_icon_wrap($item); ?>
                        <h3 class="services__card-title">
                            <a class="services__card-link" href="<?php echo htmlspecialchars(sodeystvie_service_page_href($item), ENT_QUOTES, 'UTF-8'); ?>"<?php echo site_ve_attrs('title', 'text', 'Название услуги', 'services', $sid); ?>>
                                <?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h3>
                        <p class="services__card-text"<?php echo site_ve_attrs('short', 'textarea', 'Краткое описание', 'services', $sid); ?>><?php echo htmlspecialchars($item['short'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
