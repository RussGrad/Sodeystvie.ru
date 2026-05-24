<?php

declare(strict_types=1);

/**
 * Секция «Услуги» на главной: сетка карточек.
 */

require_once __DIR__ . '/services-catalog.php';
require_once __DIR__ . '/services-icon.php';

$servicesItems = sodeystvie_services_catalog();

?>
<section class="services" aria-labelledby="services-title">
    <div class="container">
        <div class="services__head">
            <h2 class="services__title" id="services-title">Услуги</h2>
            <a class="services__all-link" href="/services/">Все услуги</a>
        </div>
        <ul class="services__grid">
            <?php foreach ($servicesItems as $item) { ?>
                <li class="services__cell">
                    <article class="services__card">
                        <div class="services__icon-wrap" aria-hidden="true">
                            <?php sodeystvie_services_render_icon($item['icon']); ?>
                        </div>
                        <h3 class="services__card-title">
                            <a class="services__card-link" href="/services/#<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h3>
                        <p class="services__card-text"><?php echo htmlspecialchars($item['short'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
