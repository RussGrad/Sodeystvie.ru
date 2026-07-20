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

    // Плоский режим: MAX из PNG/WebP как маска currentColor (силуэт без фона)
    if ($flat && $type === 'max' && $custom !== null) {
        echo '<span class="messenger-links__svg messenger-links__mask messenger-links__mask--max" aria-hidden="true"></span>';

        return;
    }

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
 * Плоские монохромные иконки одного стиля (сплошной силуэт, currentColor).
 *
 * @param 'telegram'|'vk'|'max'|'whatsapp' $type
 */
function site_messenger_icon_svg_flat(string $type): string
{
    $open = '<svg class="messenger-links__svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">';
    $close = '</svg>';

    if ($type === 'telegram') {
        return $open
            . '<path fill="currentColor" d="M21.55 3.12 2.72 10.55c-.92.36-.9 1.68.04 2l4.95 1.68 1.9 5.72c.3.92 1.48 1.12 2.08.35l2.72-3.48 4.55 3.35c.74.54 1.78.16 2.02-.74L22.9 4.55c.28-.98-.6-1.8-1.35-1.43z"/>'
            . $close;
    }

    if ($type === 'vk') {
        return $open
            . '<path fill="currentColor" d="M12.79 16.24s.29-.03.44-.19c.14-.15.13-.43.13-.43s-.02-1.3.58-1.5c.59-.19 1.35.96 2.16 1.38.61.32 1.06.25 1.06.25l2.14-.03s1.12-.07.59-.95c-.04-.07-.31-.65-1.59-1.83-1.34-1.24-1.16-.52.45-1.6.98-.65 2.18-1.37 2.43-1.78.38-.61.27-.88-.21-.85-1.12.08-2.34-.18-2.34-.18s-.25-.03-.44.1c-.15.1-.24.34-.24.34s-.44 1.17-1.01 2.16c-1.22 2.08-1.71 2.19-1.91 2.06-.46-.3-.35-1.21-.35-1.86 0-2.02.32-2.86-.63-3.08-.32-.08-.55-.13-1.36-.13-1.04-.01-1.93 0-2.42.21-.33.14-.59.47-.43.49.19.02.63.12.86.42.3.4.29 1.29.29 1.29s.17 2.52-.4 2.83c-.39.22-.93-.22-2.07-2.22-.59-1.02-1.03-2.14-1.03-2.14s-.09-.22-.24-.33c-.19-.14-.45-.19-.45-.19l-1.66.03s-.25.01-.34.12c-.08.09-.01.28-.01.28s1.31 3.06 2.79 4.6c1.36 1.41 2.9 1.32 2.9 1.32h.7z"/>'
            . $close;
    }

    if ($type === 'whatsapp') {
        // Сплошной диск с хвостом + вырез телефона (тот же визуальный вес, что у Telegram)
        return $open
            . '<path fill="currentColor" fill-rule="evenodd" d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.33 4.95L2 22l5.25-1.38a9.86 9.86 0 0 0 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2Zm3.95 11.78c-.22-.11-1.32-.65-1.52-.72-.2-.08-.35-.11-.5.11-.15.22-.57.72-.7.87-.13.15-.26.17-.48.06-.22-.11-.93-.34-1.77-1.09-.66-.58-1.1-1.3-1.23-1.52-.12-.22-.01-.34.1-.45.1-.1.22-.26.33-.39.11-.13.15-.22.22-.37.07-.15.04-.28-.02-.39-.06-.11-.5-1.2-.68-1.64-.18-.44-.36-.38-.5-.39h-.42c-.15 0-.39.06-.59.28-.2.22-.77.75-.77 1.84s.79 2.13.9 2.28c.11.15 1.55 2.37 3.76 3.32.53.23.94.37 1.26.47.53.17 1.01.14 1.39.09.42-.06 1.32-.54 1.51-1.06.19-.52.19-.97.13-1.06-.06-.1-.2-.15-.42-.26Z"/>'
            . $close;
    }

    // MAX — fallback: кольцо с хвостом внутрь
    return $open
        . '<path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="'
        . 'M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm0 4.2A5.8 5.8 0 1 0 12 17.8 5.8 5.8 0 0 0 12 6.2Zm-4.35 9.85c.4-.65.2-1.5-.45-1.85-.55-.3-1.25-.15-1.65.4-.7.95-1.85 1.55-3.15 1.7 1.35.85 3.1 1.05 4.6.3.35-.18.7-.38.9-.65.08-.07.17-.14.25-.2-.08.1-.17.2-.25.2-.1.08-.2.15-.3.2Z'
        . '"/>'
        . $close;
}
