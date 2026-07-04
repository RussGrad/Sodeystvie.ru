<?php

declare(strict_types=1);

require_once __DIR__ . '/site-image.php';

/**
 * Блок направлений: Продать / Купить / Сдать / Снять.
 */

$dealCards = [
    [
        'variant' => 'sell',
        'title' => 'Продать',
        'subtitle' => 'Квартиру',
        'action' => 'lead',
        'topic' => 'sell-evaluation',
        'aria' => 'Оставить заявку на продажу квартиры',
        'imageAlt' => 'Консультация по продаже квартиры',
    ],
    [
        'variant' => 'buy',
        'title' => 'Купить',
        'subtitle' => 'Квартиру',
        'action' => 'link',
        'href' => '/catalog/?operation=buy&type=flat',
        'aria' => 'Перейти в каталог покупки квартир',
        'imageAlt' => 'Подбор квартиры с риэлтором',
    ],
    [
        'variant' => 'rent-out',
        'title' => 'Сдать',
        'subtitle' => 'Квартиру',
        'action' => 'lead',
        'topic' => 'rent-out',
        'aria' => 'Оставить заявку на сдачу квартиры',
        'imageAlt' => 'Сдача квартиры в аренду',
    ],
    [
        'variant' => 'rent-in',
        'title' => 'Снять',
        'subtitle' => 'Квартиру',
        'action' => 'link',
        'href' => '/catalog/?operation=rent&type=flat',
        'aria' => 'Перейти в каталог аренды квартир',
        'imageAlt' => 'Просмотр квартиры в аренду',
    ],
];

function site_deal_card_image_path(string $variant): ?string
{
    $safe = preg_replace('/[^a-z-]/', '', $variant) ?: 'sell';
    $dir = dirname(__DIR__) . '/assets/deal-cards';
    $webBase = '/assets/deal-cards/' . $safe;

    foreach (['.jpg', '.jpeg', '.png', '.webp'] as $ext) {
        if (is_file($dir . '/' . $safe . $ext)) {
            return $webBase . $ext;
        }
    }

    return null;
}

/**
 * @param string $variant
 * @param string $alt
 */
function site_render_deal_card_visual(string $variant, string $alt = ''): void
{
    $safe = preg_replace('/[^a-z-]/', '', $variant) ?: 'sell';
    $imagePath = site_deal_card_image_path($variant);
    $photoClass = $imagePath !== null ? ' deal-cards__visual--photo' : '';
    ?>
    <div class="deal-cards__visual deal-cards__visual--<?php echo htmlspecialchars($safe, ENT_QUOTES, 'UTF-8'); ?><?php echo $photoClass; ?>">
        <?php if ($imagePath !== null) {
            echo site_render_static_picture(
                $imagePath,
                $alt,
                'deal-cards__photo',
                'width="560" height="720"'
            );
        } elseif ($safe === 'sell') { ?>
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M10 34V18l14-8 14 8v16" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M20 34v-8h8v8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M30 22l6-3M18 22l-6-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        <?php } elseif ($safe === 'buy') { ?>
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="21" cy="21" r="9" stroke="currentColor" stroke-width="2"/>
                <path d="M28 28l9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M18 21h6M21 18v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        <?php } elseif ($safe === 'rent-out') { ?>
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="16" cy="28" r="7" stroke="currentColor" stroke-width="2"/>
                <path d="M23 28h14a3 3 0 0 0 0-6h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M33 22v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        <?php } else { ?>
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M8 20l16-10 16 10v18H8V20z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M20 38V26h8v12" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M8 20h32" stroke="currentColor" stroke-width="2"/>
            </svg>
        <?php } ?>
    </div>
    <?php
}

/**
 * @param array<string, string> $card
 */
function site_render_deal_card_inner(array $card): void
{
    $title = (string) ($card['title'] ?? '');
    $subtitle = (string) ($card['subtitle'] ?? '');
    $variant = (string) ($card['variant'] ?? 'sell');
    $imageAlt = (string) ($card['imageAlt'] ?? $title);
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
        <?php site_render_deal_card_visual($variant, $imageAlt); ?>
    </div>
    <?php
}

?>
<section class="deal-cards" aria-label="Направления работы с недвижимостью">
    <div class="container">
        <div class="deal-cards__leadspace">
            <div class="deal-cards__kicker">
                <p class="deal-cards__kicker-text">с нами легко</p>
                <span class="deal-cards__kicker-arrow" aria-hidden="true"></span>
            </div>
        </div>
        <div class="deal-cards__grid">
            <?php foreach ($dealCards as $card) {
                $aria = (string) ($card['aria'] ?? ($card['title'] ?? ''));
                $variant = htmlspecialchars((string) ($card['variant'] ?? ''), ENT_QUOTES, 'UTF-8');
                if (($card['action'] ?? '') === 'lead') {
                    ?>
                    <button
                        type="button"
                        class="deal-cards__item deal-cards__item--<?php echo $variant; ?>"
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
                    class="deal-cards__item deal-cards__item--<?php echo $variant; ?>"
                    href="<?php echo htmlspecialchars((string) ($card['href'] ?? '/catalog/'), ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="<?php echo htmlspecialchars($aria, ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <?php site_render_deal_card_inner($card); ?>
                </a>
            <?php } ?>
        </div>
    </div>
</section>
