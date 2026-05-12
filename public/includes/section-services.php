<?php

declare(strict_types=1);

/**
 * Секция «Услуги»: сетка карточек с иконкой, заголовком и текстом.
 */

$servicesItems = [
    [
        'title' => 'Продажа недвижимости',
        'text' => 'Продадим вашу недвижимость выгодно, быстро и безопасно',
        'icon' => 'sale',
    ],
    [
        'title' => 'Проверка недвижимости',
        'text' => 'Досконально проверим объект и собственника, минимизируем риски',
        'icon' => 'check',
    ],
    [
        'title' => 'Оценка недвижимости',
        'text' => 'Рассчитаем стоимость вашей недвижимости и поможем продать дороже',
        'icon' => 'valuation',
    ],
    [
        'title' => 'Страхование недвижимости',
        'text' => 'Защитим вас и ваше имущество от любых непредвиденных обстоятельств',
        'icon' => 'insurance',
    ],
    [
        'title' => 'Ипотека онлайн',
        'text' => 'Подберём самое выгодное и прозрачное ипотечное предложение',
        'icon' => 'mortgage',
    ],
    [
        'title' => 'Онлайн сделка',
        'text' => 'Оформим покупку или продажу недвижимости онлайн',
        'icon' => 'online',
    ],
];

?>
<section class="services" aria-labelledby="services-title">
    <div class="container">
        <h2 class="services__title" id="services-title">Услуги</h2>
        <ul class="services__grid">
            <?php foreach ($servicesItems as $item) { ?>
                <li class="services__cell">
                    <article class="services__card">
                        <div class="services__icon-wrap" aria-hidden="true">
                            <?php
                            switch ($item['icon']) {
                                case 'sale':
                                    ?>
                            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z"/>
                                <circle cx="12" cy="10.5" r="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
                                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M12 12.5v2.5"/>
                            </svg>
                                    <?php
                                    break;
                                case 'check':
                                    ?>
                            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M8 4h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8l-4-4V6a2 2 0 0 1 2-2Z"/>
                                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-5"/>
                            </svg>
                                    <?php
                                    break;
                                case 'valuation':
                                    ?>
                            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="10.5" cy="10.5" r="5.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
                                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="m16 16 4.5 4.5"/>
                                <path fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" d="M9 7.5v9M7.25 10h3.25a1.35 1.35 0 0 1 0 2.7H8.1a1.35 1.35 0 0 0 0 2.7H11"/>
                            </svg>
                                    <?php
                                    break;
                                case 'insurance':
                                    ?>
                            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M12 3 5 6v6c0 4.5 3.2 8.7 7 10 3.8-1.3 7-5.5 7-10V6l-7-3Z"/>
                            </svg>
                                    <?php
                                    break;
                                case 'mortgage':
                                    ?>
                            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M8 17 16 7"/>
                                <circle cx="8.5" cy="7.5" r="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
                                <circle cx="15.5" cy="16.5" r="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                                    <?php
                                    break;
                                case 'online':
                                    ?>
                            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M6 4h12a2 2 0 0 1 2 2v11H4V6a2 2 0 0 1 2-2Z"/>
                                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M4 17h16v3H4v-3Z"/>
                                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="m15 8 3 2-3 2"/>
                            </svg>
                                    <?php
                                    break;
                            }
                            ?>
                        </div>
                        <h3 class="services__card-title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="services__card-text"><?php echo htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
