<?php

declare(strict_types=1);

/**
 * Блок направлений: Продать / Купить / Сдать / Снять (как на референсе).
 */

require_once __DIR__ . '/site-image.php';

$dealCards = [
    [
        'title' => 'Продать',
        'subtitle' => 'КВАРТИРУ',
        'image' => '/assets/deal-cards/sell.png',
        'alt' => 'Продажа квартиры с сопровождением риэлтора',
        'action' => 'lead',
        'topic' => 'sell-evaluation',
        'aria' => 'Оставить заявку на продажу квартиры',
    ],
    [
        'title' => 'Купить',
        'subtitle' => 'КВАРТИРУ',
        'image' => '/assets/deal-cards/buy.png',
        'alt' => 'Подбор квартиры для покупки',
        'action' => 'link',
        'href' => '/catalog/?operation=buy&type=flat',
        'aria' => 'Перейти в каталог покупки квартир',
    ],
    [
        'title' => 'Сдать',
        'subtitle' => 'КВАРТИРУ',
        'image' => '/assets/deal-cards/rent-out.png',
        'alt' => 'Сдача квартиры в аренду',
        'action' => 'lead',
        'topic' => 'rent-out',
        'aria' => 'Оставить заявку на сдачу квартиры',
    ],
    [
        'title' => 'Снять',
        'subtitle' => 'КВАРТИРУ',
        'image' => '/assets/deal-cards/rent-in.png',
        'alt' => 'Аренда квартиры в Иркутске',
        'action' => 'link',
        'href' => '/catalog/?operation=rent&type=flat',
        'aria' => 'Перейти в каталог аренды квартир',
    ],
];

/**
 * @param array<string, string> $card
 */
function site_render_deal_card_inner(array $card): void
{
    $title = (string) ($card['title'] ?? '');
    $subtitle = (string) ($card['subtitle'] ?? '');
    $image = (string) ($card['image'] ?? '');
    $alt = (string) ($card['alt'] ?? $title);
    ?>
    <div class="deal-cards__head">
        <div class="deal-cards__labels">
            <span class="deal-cards__title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="deal-cards__subtitle"><?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <span class="deal-cards__arrow" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </div>
    <div class="deal-cards__media">
        <?php
        if ($image !== '') {
            echo site_render_static_picture(
                $image,
                $alt,
                'deal-cards__photo',
                'width="280" height="360" loading="lazy" decoding="async"',
            );
        }
        ?>
    </div>
    <?php
}

?>
<section class="deal-cards" aria-label="Направления работы с недвижимостью">
    <div class="deal-cards__grid">
        <?php foreach ($dealCards as $card) {
            $aria = (string) ($card['aria'] ?? ($card['title'] ?? ''));
            if (($card['action'] ?? '') === 'lead') {
                ?>
                <button
                    type="button"
                    class="deal-cards__item"
                    aria-label="<?php echo htmlspecialchars($aria, ENT_QUOTES, 'UTF-8'); ?>"
                    data-lead-open
                    data-lead-topic="<?php echo htmlspecialchars((string) ($card['topic'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <?php site_render_deal_card_inner($card); ?>
                </button>
                <?php
                continue;
            }
            ?>
            <a
                class="deal-cards__item"
                href="<?php echo htmlspecialchars((string) ($card['href'] ?? '/catalog/'), ENT_QUOTES, 'UTF-8'); ?>"
                aria-label="<?php echo htmlspecialchars($aria, ENT_QUOTES, 'UTF-8'); ?>"
            >
                <?php site_render_deal_card_inner($card); ?>
            </a>
        <?php } ?>
    </div>
</section>
