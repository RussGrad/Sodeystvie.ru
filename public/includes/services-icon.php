<?php

declare(strict_types=1);

/**
 * SVG-иконки услуг (stroke, currentColor) и кастомные изображения.
 */

function site_service_custom_image_path(string $id): ?string
{
    $safe = preg_replace('/[^a-z0-9_-]/i', '', $id) ?? '';
    if ($safe === '') {
        return null;
    }

    $dir = dirname(__DIR__) . '/assets/services';
    $webBase = '/assets/services/' . $safe;

    foreach (['.png', '.webp', '.jpg', '.jpeg', '.svg'] as $ext) {
        if (is_file($dir . '/' . $safe . $ext)) {
            return $webBase . $ext;
        }
    }

    return null;
}

/**
 * Рендер иконки или загруженного изображения услуги.
 *
 * @param array{id?: string, icon?: string} $item
 */
function sodeystvie_services_render_visual(array $item): void
{
    $id = (string) ($item['id'] ?? '');
    $icon = (string) ($item['icon'] ?? 'realtor');
    $custom = $id !== '' ? site_service_custom_image_path($id) : null;

    if ($custom !== null) {
        $ext = strtolower(pathinfo($custom, PATHINFO_EXTENSION));
        if ($ext === 'svg') {
            echo '<img class="services__icon services__icon--custom" src="'
                . htmlspecialchars($custom, ENT_QUOTES, 'UTF-8')
                . '" alt="" width="28" height="28" decoding="async">';
        } else {
            require_once __DIR__ . '/site-image.php';
            echo site_render_static_picture(
                $custom,
                '',
                'services__icon services__icon--custom',
                'width="28" height="28"'
            );
        }

        return;
    }

    sodeystvie_services_render_icon($icon);
}

/**
 * @param array{id?: string, icon?: string} $item
 * @param string $extraClass дополнительные классы на wrap
 */
function sodeystvie_services_render_icon_wrap(array $item, string $extraClass = ''): void
{
    if (!function_exists('site_ve_attrs')) {
        require_once __DIR__ . '/visual-editor.php';
    }

    $id = (string) ($item['id'] ?? '');
    $icon = (string) ($item['icon'] ?? 'realtor');
    $hasImage = $id !== '' && site_service_custom_image_path($id) !== null;
    $class = trim('services__icon-wrap ' . $extraClass);
    $ve = site_ve_attrs('icon', 'icon', 'Иконка / изображение услуги', 'services', $id, $icon);
    if ($ve !== '' && $hasImage) {
        $ve .= ' data-ve-has-image="1"';
    }
    ?>
    <div class="<?php echo htmlspecialchars($class, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"<?php echo $ve; ?>>
        <?php sodeystvie_services_render_visual($item); ?>
    </div>
    <?php
}

function sodeystvie_services_render_icon(string $icon): void
{
    switch ($icon) {
        case 'valuation':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="10.5" cy="10.5" r="5.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="m16 16 4.5 4.5"/>
                <path fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" d="M9 7.5v9M7.25 10h3.25a1.35 1.35 0 0 1 0 2.7H8.1a1.35 1.35 0 0 0 0 2.7H11"/>
            </svg>
            <?php
            break;
        case 'realtor':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M9 14h6M12 11v6"/>
            </svg>
            <?php
            break;
        case 'selection':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="10" cy="10" r="6" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="m15.5 15.5 4 4"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M7.5 10h5M10 7.5v5"/>
            </svg>
            <?php
            break;
        case 'analytics':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M4 19V5M4 19h16"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M8 15V11M12 15V8M16 15v-3"/>
            </svg>
            <?php
            break;
        case 'chain':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M8 12h8M12 8v8"/>
                <circle cx="7" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="17" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M9.5 12H6M18 12h-1.5"/>
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
        case 'legal':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M6 4h12v16H6z"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M9 8h6M9 12h6M9 16h4"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M12 4v3"/>
            </svg>
            <?php
            break;
        default:
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            <?php
    }
}
