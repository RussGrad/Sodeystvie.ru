<?php

declare(strict_types=1);

/**
 * Лучшие предложения: тестовые карточки объектов (новостройки и вторичка), сетка как у каталога.
 */

$featuredListings = [
    [
        'title' => '2-комн. квартира, 58 м²',
        'address' => 'ЖК «Северный», Ангарск',
        'rooms' => '2 комнаты',
        'area' => '58 м²',
        'floor' => '12 из 17',
        'price' => '4 850 000 ₽',
        'badge' => 'Новостройка',
        'badge_type' => 'new',
        'tone' => 1,
        'href' => '/catalog/?demo=1',
    ],
    [
        'title' => 'Студия, 28 м²',
        'address' => 'ЖК «Лесной», Ангарск',
        'rooms' => 'Студия',
        'area' => '28 м²',
        'floor' => '5 из 25',
        'price' => '2 950 000 ₽',
        'badge' => 'Новостройка',
        'badge_type' => 'new',
        'tone' => 2,
        'href' => '/catalog/?demo=2',
    ],
    [
        'title' => '3-комн. квартира, 72 м²',
        'address' => 'ул. Ленина, 14, Ангарск',
        'rooms' => '3 комнаты',
        'area' => '72 м²',
        'floor' => '4 из 5',
        'price' => '6 200 000 ₽',
        'badge' => 'Вторичка',
        'badge_type' => 'resale',
        'tone' => 3,
        'href' => '/catalog/?demo=3',
    ],
    [
        'title' => '1-комн. квартира, 38 м²',
        'address' => 'ЖК «Речной», Ангарск',
        'rooms' => '1 комната',
        'area' => '38 м²',
        'floor' => '8 из 20',
        'price' => '3 420 000 ₽',
        'badge' => 'Новостройка',
        'badge_type' => 'new',
        'tone' => 4,
        'href' => '/catalog/?demo=4',
    ],
    [
        'title' => '4-комн. квартира, 98 м²',
        'address' => 'пр-т Мира, 112, Ангарск',
        'rooms' => '4 комнаты',
        'area' => '98 м²',
        'floor' => '2 из 9',
        'price' => '7 900 000 ₽',
        'badge' => 'Акция',
        'badge_type' => 'sale',
        'tone' => 5,
        'href' => '/catalog/?demo=5',
    ],
    [
        'title' => '2-комн. квартира, 54 м²',
        'address' => 'ЖК «Солнечный», Ангарск',
        'rooms' => '2 комнаты',
        'area' => '54 м²',
        'floor' => '14 из 22',
        'price' => '4 100 000 ₽',
        'badge' => 'Новостройка',
        'badge_type' => 'new',
        'tone' => 6,
        'href' => '/catalog/?demo=6',
    ],
    [
        'title' => 'Пентхаус, 120 м²',
        'address' => 'ЖК «Панорама», Ангарск',
        'rooms' => '4 комнаты',
        'area' => '120 м²',
        'floor' => '24 из 24',
        'price' => '12 500 000 ₽',
        'badge' => 'Новостройка',
        'badge_type' => 'new',
        'tone' => 7,
        'href' => '/catalog/?demo=7',
    ],
    [
        'title' => '2-комн. квартира, 48 м²',
        'address' => 'ул. Кирова, 7, Ангарск',
        'rooms' => '2 комнаты',
        'area' => '48 м²',
        'floor' => '3 из 5',
        'price' => '3 890 000 ₽',
        'badge' => 'Вторичка',
        'badge_type' => 'resale',
        'tone' => 8,
        'href' => '/catalog/?demo=8',
    ],
];

?>
<section class="featured" aria-labelledby="featured-heading">
    <div class="container">
        <header class="featured__header">
            <div class="featured__intro">
                <h2 class="featured__title" id="featured-heading">Лучшие предложения</h2>
                <p class="featured__lead">Новостройки и проверенные объекты — подборка для ознакомления (тестовые данные).</p>
            </div>
            <a class="featured__to-catalog" href="/catalog/">Весь каталог</a>
        </header>

        <ul class="featured__grid">
            <?php foreach ($featuredListings as $item) { ?>
                <li class="featured__cell">
                    <article class="featured-card">
                        <a class="featured-card__link" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="featured-card__media featured-card__media--tone-<?php echo (int) $item['tone']; ?>" aria-hidden="true">
                                <span class="featured-card__badge featured-card__badge--<?php echo htmlspecialchars($item['badge_type'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($item['badge'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                            <div class="featured-card__body">
                                <h3 class="featured-card__title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="featured-card__address"><?php echo htmlspecialchars($item['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <ul class="featured-card__meta">
                                    <li><?php echo htmlspecialchars($item['rooms'], ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><?php echo htmlspecialchars($item['area'], ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><?php echo htmlspecialchars($item['floor'], ENT_QUOTES, 'UTF-8'); ?></li>
                                </ul>
                                <p class="featured-card__price"><?php echo htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </a>
                    </article>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
