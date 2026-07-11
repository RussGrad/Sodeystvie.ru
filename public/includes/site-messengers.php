<?php

declare(strict_types=1);

require_once __DIR__ . '/site-content.php';

/** @return list<string> */
function site_messenger_types(): array
{
    return ['telegram', 'vk', 'max', 'whatsapp'];
}

function site_messenger_label(string $type): string
{
    return match ($type) {
        'telegram' => 'Telegram',
        'vk' => 'ВКонтакте',
        'max' => 'MAX',
        'whatsapp' => 'WhatsApp',
        default => $type,
    };
}

function site_messenger_icons_dir(): string
{
    return dirname(__DIR__) . '/assets/icons/messengers';
}

function site_messenger_custom_icon_src(string $type): ?string
{
    if (!in_array($type, site_messenger_types(), true)) {
        return null;
    }

    $dir = site_messenger_icons_dir();
    foreach (['svg', 'webp', 'png'] as $ext) {
        $file = $dir . '/' . $type . '.' . $ext;
        if (is_readable($file)) {
            $ver = (string) (@filemtime($file) ?: '');

            return '/assets/icons/messengers/' . $type . '.' . $ext
                . ($ver !== '' ? '?v=' . rawurlencode($ver) : '');
        }
    }

    return null;
}

function site_messenger_is_visible(string $type): bool
{
    if (!in_array($type, site_messenger_types(), true)) {
        return false;
    }

    $flag = site_content_setting('messenger_show_' . $type, '');
    if ($flag === '0') {
        return false;
    }

    return site_messenger_url($type) !== null;
}

function site_messenger_url(string $type): ?string
{
    $url = match ($type) {
        'telegram' => site_messenger_telegram_url_resolved(),
        'vk' => site_messenger_vk_url_resolved(),
        'max' => site_messenger_max_url_resolved(),
        'whatsapp' => site_messenger_whatsapp_url_resolved(),
        default => '',
    };

    $url = trim($url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return null;
    }

    return $url;
}

function site_messenger_telegram_url_resolved(): string
{
    $fromContent = site_content_setting('telegram_url', '');
    if ($fromContent !== '' && preg_match('#^https?://#i', $fromContent)) {
        return $fromContent;
    }

    if (!function_exists('site_telegram_url')) {
        require_once __DIR__ . '/config.php';
    }

    return site_telegram_url() ?? '';
}

function site_messenger_vk_url_resolved(): string
{
    $fromContent = site_content_setting('vk_url', '');
    if ($fromContent !== '' && preg_match('#^https?://#i', $fromContent)) {
        return $fromContent;
    }

    if (!function_exists('site_vk_url')) {
        require_once __DIR__ . '/config.php';
    }

    $fromEnv = site_vk_url();

    return $fromEnv ?? '';
}

function site_messenger_max_url_resolved(): string
{
    $fromContent = site_content_setting('max_url', '');
    if ($fromContent !== '' && preg_match('#^https?://#i', $fromContent)) {
        return $fromContent;
    }

    if (!function_exists('site_max_url')) {
        require_once __DIR__ . '/config.php';
    }

    return site_max_url();
}

function site_messenger_whatsapp_url_resolved(): string
{
    $fromContent = site_content_setting('whatsapp_url', '');
    if ($fromContent !== '' && preg_match('#^https?://#i', $fromContent)) {
        return $fromContent;
    }

    if (!function_exists('site_whatsapp_url')) {
        require_once __DIR__ . '/config.php';
    }

    return site_whatsapp_url() ?? '';
}

/**
 * @return list<array{type: string, href: string, label: string}>
 */
function site_messenger_links_for_render(): array
{
    $out = [];
    foreach (site_messenger_types() as $type) {
        if (!site_messenger_is_visible($type)) {
            continue;
        }
        $href = site_messenger_url($type);
        if ($href === null) {
            continue;
        }
        $out[] = [
            'type' => $type,
            'href' => $href,
            'label' => site_messenger_label($type),
        ];
    }

    return $out;
}
