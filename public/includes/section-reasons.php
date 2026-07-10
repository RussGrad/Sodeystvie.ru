<?php

declare(strict_types=1);

require_once __DIR__ . '/reasons-catalog.php';

$reasonsItems = sodeystvie_reasons_catalog();
if (count($reasonsItems) === 0) {
    return;
}

?>
<section class="reasons" aria-labelledby="reasons-title">
    <div class="container reasons__inner">
        <p class="reasons__watermark" aria-hidden="true">Преимущества</p>
        <h2 class="reasons__title" id="reasons-title">
            И еще <strong>8 причин</strong> обратиться к нам уже сегодня
        </h2>
        <ul class="reasons__grid">
            <?php foreach ($reasonsItems as $item) { ?>
                <li class="reasons__item">
                    <div class="reasons__item-head">
                        <span class="reasons__icon" aria-hidden="true">
                            <svg class="reasons__icon-svg" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.5 9.25L7.5 12.25L13.5 5.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <h3 class="reasons__item-title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    </div>
                    <p class="reasons__item-text"><?php echo htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
