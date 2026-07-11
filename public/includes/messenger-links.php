<?php

declare(strict_types=1);

/**
 * Иконки мессенджеров (Telegram, ВКонтакте, MAX, WhatsApp).
 */
function site_render_messenger_links(
    string $listClass = 'messenger-links',
    string $linkClass = 'messenger-links__item',
    bool $showLabels = false,
): void {
    require_once __DIR__ . '/site-messengers.php';

    $links = site_messenger_links_for_render();
    if (count($links) === 0) {
        return;
    }

    $flat = str_contains($listClass, 'messenger-links--flat');
    $listMod = $showLabels ? ' messenger-links--labeled' : '';
    echo '<ul class="' . htmlspecialchars($listClass, ENT_QUOTES, 'UTF-8') . $listMod . '">';

    foreach ($links as $link) {
        site_render_messenger_link_item(
            $linkClass,
            '--' . $link['type'],
            $link['href'],
            $link['label'],
            $showLabels,
            $link['type'],
            null,
            $flat,
        );
    }

    echo '</ul>';
}

/**
 * @param 'telegram'|'vk'|'max'|'whatsapp' $type
 */
function site_render_messenger_link_item(
    string $linkClass,
    string $modifier,
    string $href,
    string $label,
    bool $showLabels,
    string $type,
    ?string $imgSrc = null,
    bool $flat = false,
): void {
    echo '<li class="messenger-links__cell">';
    echo '<a class="' . htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($linkClass . $modifier, ENT_QUOTES, 'UTF-8') . '"';
    echo ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer"';
    echo ' aria-label="Написать в ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">';

    site_render_messenger_icon($type, $flat, $imgSrc);

    if (!$showLabels) {
        echo '<span class="visually-hidden">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    echo '</a>';

    if ($showLabels) {
        echo '<span class="messenger-links__name">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    echo '</li>';
}

/**
 * @param 'telegram'|'vk'|'max'|'whatsapp' $type
 */
function site_render_messenger_icon(string $type, bool $flat = false, ?string $imgSrc = null): void
{
    require_once __DIR__ . '/site-messengers.php';

    $custom = site_messenger_custom_icon_src($type);
    if ($custom !== null) {
        echo '<img class="messenger-links__img" src="' . htmlspecialchars($custom, ENT_QUOTES, 'UTF-8') . '" width="24" height="24" alt="" decoding="async">';
        return;
    }

    if ($flat) {
        echo site_messenger_icon_svg_flat($type);
        return;
    }

    if ($type === 'telegram' || $type === 'vk') {
        echo site_messenger_icon_svg_branded($type);
    } elseif ($type === 'max') {
        $maxIconWebp = __DIR__ . '/../assets/icons/max-messenger.webp';
        $legacySrc = is_readable($maxIconWebp) ? '/assets/icons/max-messenger.webp' : '/assets/icons/max-messenger.png';
        echo '<img class="messenger-links__img" src="' . htmlspecialchars($legacySrc, ENT_QUOTES, 'UTF-8') . '" width="24" height="24" alt="" decoding="async">';
    } elseif ($type === 'whatsapp') {
        echo site_messenger_icon_svg_flat('whatsapp');
    } elseif ($imgSrc !== null) {
        echo '<img class="messenger-links__img" src="' . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . '" width="24" height="24" alt="" decoding="async">';
    }
}

/**
 * @param 'telegram'|'vk' $type
 */
function site_messenger_icon_svg_branded(string $type): string
{
    if ($type === 'telegram') {
        return '<svg class="messenger-links__svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">'
            . '<path fill="currentColor" d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>'
            . '</svg>';
    }

    return '<svg class="messenger-links__svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">'
        . '<path fill="currentColor" d="M12.785 16.241s.288-.032.436-.194c.136-.148.132-.427.132-.427s-.02-1.304.58-1.496c.592-.188 1.354.956 2.16 1.377.605.318 1.064.248 1.064.248l2.141-.03s1.118-.07.587-.95c-.043-.073-.308-.648-1.588-1.833-1.344-1.242-1.163-.521.454-1.595.983-.655 2.183-1.377 2.425-1.78.378-.61.27-.885-.206-.848-1.12.083-2.337-.178-2.337-.178s-.252-.032-.438.095c-.149.104-.244.34-.244.34s-.437 1.168-1.01 2.164c-1.218 2.075-1.707 2.184-1.907 2.056-.465-.302-.349-1.21-.349-1.856 0-2.016.323-2.86-.631-3.078-.317-.076-.55-.126-1.363-.134-1.042-.01-1.923.003-2.42.208-.332.145-.588.468-.432.487.192.023.626.116.855.425.296.397.286 1.288.286 1.288s.17 2.52-.397 2.833c-.39.214-.926-.223-2.073-2.223-.588-1.015-1.033-2.142-1.033-2.142s-.086-.214-.24-.33c-.186-.14-.446-.184-.446-.184l-1.658.03s-.249.015-.34.116c-.081.09-.006.277-.006.277s1.305 3.057 2.785 4.597c1.358 1.413 2.903 1.32 2.903 1.32h.697z"/>'
        . '</svg>';
}

/**
 * @param 'telegram'|'vk'|'max'|'whatsapp' $type
 */
function site_messenger_icon_svg_flat(string $type): string
{
    if ($type === 'telegram') {
        return '<svg class="messenger-links__svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">'
            . '<path fill="currentColor" d="M21.433 4.009a1.12 1.12 0 0 0-1.207-.245l-16.5 6.5a1.12 1.12 0 0 0 .042 2.085l4.972 1.745 1.745 4.972a1.12 1.12 0 0 0 2.085.042l6.5-16.5a1.12 1.12 0 0 0-.637-1.609z"/>'
            . '</svg>';
    }

    if ($type === 'vk') {
        return '<svg class="messenger-links__svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">'
            . '<path fill="currentColor" d="M12.785 16.241s.288-.032.436-.194c.136-.148.132-.427.132-.427s-.02-1.304.58-1.496c.592-.188 1.354.956 2.16 1.377.605.318 1.064.248 1.064.248l2.141-.03s1.118-.07.587-.95c-.043-.073-.308-.648-1.588-1.833-1.344-1.242-1.163-.521.454-1.595.983-.655 2.183-1.377 2.425-1.78.378-.61.27-.885-.206-.848-1.12.083-2.337-.178-2.337-.178s-.252-.032-.438.095c-.149.104-.244.34-.244.34s-.437 1.168-1.01 2.164c-1.218 2.075-1.707 2.184-1.907 2.056-.465-.302-.349-1.21-.349-1.856 0-2.016.323-2.86-.631-3.078-.317-.076-.55-.126-1.363-.134-1.042-.01-1.923.003-2.42.208-.332.145-.588.468-.432.487.192.023.626.116.855.425.296.397.286 1.288.286 1.288s.17 2.52-.397 2.833c-.39.214-.926-.223-2.073-2.223-.588-1.015-1.033-2.142-1.033-2.142s-.086-.214-.24-.33c-.186-.14-.446-.184-.446-.184l-1.658.03s-.249.015-.34.116c-.081.09-.006.277-.006.277s1.305 3.057 2.785 4.597c1.358 1.413 2.903 1.32 2.903 1.32h.697z"/>'
            . '</svg>';
    }

    if ($type === 'whatsapp') {
        return '<svg class="messenger-links__svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">'
            . '<path fill="currentColor" d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.33 4.95L2 22l5.31-1.39a9.9 9.9 0 0 0 4.73 1.21h.01c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm0 18.08h-.01a8.1 8.1 0 0 1-4.13-1.13l-.3-.18-3.15.82.84-3.07-.19-.31a8.08 8.08 0 0 1-1.24-4.31c0-4.46 3.63-8.09 8.1-8.09 4.46 0 8.09 3.63 8.09 8.09s-3.63 8.09-8.09 8.09zm4.45-6.05c-.24-.12-1.44-.71-1.66-.79-.22-.08-.38-.12-.54.12-.16.24-.62.79-.76.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.18-.71-.63-1.19-1.41-1.33-1.65-.14-.24-.02-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.48-.4-.41-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.69 2.58 4.09 3.62.57.25 1.01.4 1.36.51.57.18 1.09.15 1.5.09.46-.07 1.44-.59 1.64-1.16.2-.57.2-1.06.14-1.16-.06-.1-.22-.16-.46-.28z"/>'
            . '</svg>';
    }

    return '<svg class="messenger-links__svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">'
        . '<path fill="currentColor" d="M6 4h12a2 2 0 0 1 2 2v8.2a2 2 0 0 1-2 2h-6.1L8.8 20.6a1 1 0 0 1-1.6-.8V16.2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm2.2 5.2h7.6v1.5H8.2V9.2zm0 3h5.2v1.5H8.2v-1.5z"/>'
        . '</svg>';
}
