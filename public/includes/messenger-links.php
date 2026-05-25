<?php

declare(strict_types=1);

/**
 * Иконки мессенджеров и соцсетей (Telegram, ВКонтакте, MAX).
 *
 * @param string $listClass CSS-класс на <ul>
 * @param string $linkClass CSS-класс на <a> (модификаторы --tg, --vk, --max)
 * @param bool $showLabels подписи под иконками
 */
function site_render_messenger_links(
    string $listClass = 'messenger-links',
    string $linkClass = 'messenger-links__item',
    bool $showLabels = false,
): void {
    $telegramUrl = site_telegram_url();
    $vkUrl = site_vk_url();
    $maxUrl = site_max_url();

    if ($telegramUrl === null && $vkUrl === null && $maxUrl === '') {
        return;
    }

    $listMod = $showLabels ? ' messenger-links--labeled' : '';
    echo '<ul class="' . htmlspecialchars($listClass, ENT_QUOTES, 'UTF-8') . $listMod . '">';

    if ($telegramUrl !== null) {
        site_render_messenger_link_item($linkClass, '--tg', $telegramUrl, 'Telegram', $showLabels, 'telegram');
    }

    if ($vkUrl !== null) {
        site_render_messenger_link_item($linkClass, '--vk', $vkUrl, 'ВКонтакте', $showLabels, 'vk');
    }

    if ($maxUrl !== '') {
        $maxIconWebp = __DIR__ . '/../assets/icons/max-messenger.webp';
        $maxIconSrc = is_readable($maxIconWebp) ? '/assets/icons/max-messenger.webp' : '/assets/icons/max-messenger.png';
        site_render_messenger_link_item($linkClass, '--max', $maxUrl, 'MAX', $showLabels, 'max', $maxIconSrc);
    }

    echo '</ul>';
}

/**
 * @param 'telegram'|'vk'|'max' $type
 */
function site_render_messenger_link_item(
    string $linkClass,
    string $modifier,
    string $href,
    string $label,
    bool $showLabels,
    string $type,
    ?string $imgSrc = null,
): void {
    echo '<li class="messenger-links__cell">';
    echo '<a class="' . htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($linkClass . $modifier, ENT_QUOTES, 'UTF-8') . '"';
    echo ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer"';
    echo ' aria-label="Написать в ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">';

    if ($type === 'telegram') {
        echo '<svg class="messenger-links__svg" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">';
        echo '<path fill="currentColor" d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>';
        echo '</svg>';
    } elseif ($type === 'vk') {
        echo '<svg class="messenger-links__svg" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">';
        echo '<path fill="currentColor" d="M12.785 16.241s.288-.032.436-.194c.136-.148.132-.427.132-.427s-.02-1.304.58-1.496c.592-.188 1.354.956 2.16 1.377.605.318 1.064.248 1.064.248l2.141-.03s1.118-.07.587-.95c-.043-.073-.308-.648-1.588-1.833-1.344-1.242-1.163-.521.454-1.595.983-.655 2.183-1.377 2.425-1.78.378-.61.27-.885-.206-.848-1.12.083-2.337-.178-2.337-.178s-.252-.032-.438.095c-.149.104-.244.34-.244.34s-.437 1.168-1.01 2.164c-1.218 2.075-1.707 2.184-1.907 2.056-.465-.302-.349-1.21-.349-1.856 0-2.016.323-2.86-.631-3.078-.317-.076-.55-.126-1.363-.134-1.042-.01-1.923.003-2.42.208-.332.145-.588.468-.432.487.192.023.626.116.855.425.296.397.286 1.288.286 1.288s.17 2.52-.397 2.833c-.39.214-.926-.223-2.073-2.223-.588-1.015-1.033-2.142-1.033-2.142s-.086-.214-.24-.33c-.186-.14-.446-.184-.446-.184l-1.658.03s-.249.015-.34.116c-.081.09-.006.277-.006.277s1.305 3.057 2.785 4.597c1.358 1.413 2.903 1.32 2.903 1.32h.697z"/>';
        echo '</svg>';
    } elseif ($imgSrc !== null) {
        echo '<img class="messenger-links__img" src="' . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . '" width="22" height="22" alt="" decoding="async">';
    }

    if (!$showLabels) {
        echo '<span class="visually-hidden">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    echo '</a>';

    if ($showLabels) {
        echo '<span class="messenger-links__name">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    echo '</li>';
}
