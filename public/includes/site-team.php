<?php

declare(strict_types=1);

require_once __DIR__ . '/site-image.php';

/**
 * Команда на витрине: public/data/team.json (админка) → fallback CRM (showOnSite).
 * Фото локально: public/assets/team/{id}.jpg; из CRM — avatarUrl через CRM_PUBLIC_BASE.
 */

function site_team_data_path(): string
{
    return dirname(__DIR__) . '/data/team.json';
}

/**
 * @return list<array{id: string, name: string, role: string, experience: string, description: string, photo: string, telegram: string, whatsapp: string}>
 */
function site_team_all(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $fromJson = site_team_all_from_json();
    if (count($fromJson) > 0) {
        $cache = $fromJson;

        return $cache;
    }

    $cache = site_team_all_from_crm();

    return $cache;
}

/** Сайт сейчас показывает команду из CRM (в админке записей нет). */
function site_team_display_from_crm(): bool
{
    return count(site_team_all_from_json()) === 0 && count(site_team_all_from_crm()) > 0;
}

/**
 * @return list<array{id: string, name: string, role: string, experience: string, description: string, photo: string, telegram: string, whatsapp: string}>
 */
function site_team_all_from_crm(): array
{
    if (!function_exists('site_crm_team_url')) {
        return [];
    }

    $url = site_crm_team_url();
    $cacheKey = 'crm-public-team:' . $url;
    $payload = site_crm_cache_read_json($cacheKey, 600);
    if ($payload === null) {
        $payload = site_http_public_api_get($url, 10);
        if (!isset($payload['_error'])) {
            site_crm_cache_write_json($cacheKey, $payload);
        }
    }

    if (isset($payload['_error']) || !is_array($payload['items'] ?? null)) {
        return [];
    }

    $out = [];
    foreach ($payload['items'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = isset($row['id']) ? trim((string) $row['id']) : '';
        $name = isset($row['name']) ? trim((string) $row['name']) : '';
        if ($id === '' || $name === '') {
            continue;
        }

        $role = isset($row['roleLabel']) ? trim((string) $row['roleLabel']) : '';
        if ($role === '' && isset($row['role'])) {
            $role = trim((string) $row['role']);
        }

        $experience = isset($row['experience']) ? trim((string) $row['experience']) : '';
        $description = isset($row['description']) ? trim((string) $row['description']) : '';
        if ($description === '' && isset($row['bio'])) {
            $description = trim((string) $row['bio']);
        }

        $photoRaw = isset($row['photo']) && is_string($row['photo']) ? trim($row['photo']) : '';
        $photo = site_team_resolve_photo_url($photoRaw);

        $out[] = [
            'id' => $id,
            'name' => $name,
            'role' => $role,
            'experience' => $experience,
            'description' => $description,
            'photo' => $photo,
            'telegram' => '',
            'whatsapp' => '',
        ];
    }

    return $out;
}

/**
 * @return list<array{id: string, name: string, role: string, experience: string, description: string, photo: string, telegram: string, whatsapp: string}>
 */
function site_team_all_from_json(): array
{
    $path = site_team_data_path();
    if (!is_readable($path)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        return [];
    }

    $out = [];
    foreach ($decoded as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = isset($row['id']) ? trim((string) $row['id']) : '';
        $name = isset($row['name']) ? trim((string) $row['name']) : '';
        if ($id === '' || $name === '') {
            continue;
        }

        $photo = isset($row['photo']) ? trim((string) $row['photo']) : '';
        if ($photo === '') {
            $photo = site_team_photo_path($id);
        }

        $out[] = [
            'id' => $id,
            'name' => $name,
            'role' => isset($row['role']) ? trim((string) $row['role']) : '',
            'experience' => isset($row['experience']) ? trim((string) $row['experience']) : '',
            'description' => isset($row['description']) ? trim((string) $row['description']) : '',
            'photo' => $photo,
            'telegram' => isset($row['telegram']) ? trim((string) $row['telegram']) : '',
            'whatsapp' => isset($row['whatsapp']) ? trim((string) $row['whatsapp']) : '',
        ];
    }

    return $out;
}

function site_team_resolve_photo_url(string $photo): string
{
    $photo = trim($photo);
    if ($photo === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $photo)) {
        return $photo;
    }
    if (str_starts_with($photo, '/') && function_exists('site_crm_public_url')) {
        return site_crm_public_url($photo);
    }

    return $photo;
}

function site_team_member_has_photo(array $member): bool
{
    return trim((string) ($member['photo'] ?? '')) !== '';
}

function site_team_member_photo_is_local(string $photo): bool
{
    $photo = trim($photo);
    if ($photo === '' || preg_match('#^https?://#i', $photo)) {
        return false;
    }

    return str_starts_with($photo, '/');
}

function site_team_photo_path(string $id): string
{
    $safe = preg_replace('/[^a-z0-9_-]/i', '', $id) ?? '';
    if ($safe === '') {
        return '';
    }
    $publicRoot = dirname(__DIR__);
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $web = '/assets/team/' . $safe . '.' . $ext;
        if (is_readable($publicRoot . $web)) {
            return $web;
        }
    }

    return '';
}

function site_team_initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($parts) === 0) {
        return '?';
    }
    if (count($parts) === 1) {
        return mb_strtoupper(mb_substr($parts[0], 0, 1));
    }

    return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
}

/**
 * @param array{id: string, name: string, role: string, experience: string, description?: string, photo: string, telegram: string, whatsapp: string} $member
 */
function site_render_team_card(array $member): void
{
    if (!function_exists('site_ve_attrs')) {
        require_once __DIR__ . '/visual-editor.php';
    }

    $id = (string) ($member['id'] ?? '');
    $photo = trim((string) ($member['photo'] ?? ''));
    $hasLocalPhoto = $photo !== '' && site_team_member_photo_is_local($photo) && is_readable(dirname(__DIR__) . $photo);
    $hasRemotePhoto = $photo !== '' && !$hasLocalPhoto;
    $experienceRaw = trim((string) ($member['experience'] ?? ''));
    $experience = site_visual_editor_enabled()
        ? $experienceRaw
        : site_team_format_experience($experienceRaw);
    $description = trim((string) ($member['description'] ?? ''));
    $ve = site_visual_editor_enabled();
    $preview = site_team_description_preview($description);
    $expandable = !$ve && $preview['expandable'];
    ?>
    <article class="team-card<?php echo $expandable ? ' team-card--expandable' : ''; ?>"<?php echo $expandable ? ' data-team-card' : ''; ?>>
        <div class="team-card__photo-wrap">
            <?php if ($hasLocalPhoto) {
                echo site_render_static_picture(
                    $photo,
                    $member['name'],
                    'team-card__photo'
                );
            } elseif ($hasRemotePhoto) { ?>
                <img class="team-card__photo" src="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="160" height="160" loading="lazy">
            <?php } else { ?>
                <span class="team-card__initials" aria-hidden="true"><?php echo htmlspecialchars(site_team_initials($member['name']), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php } ?>
        </div>
        <h3 class="team-card__name"<?php echo site_ve_attrs('name', 'text', 'Имя', 'team', $id); ?>><?php echo htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <?php if ($member['role'] !== '' || $ve) { ?>
            <p class="team-card__role"<?php echo site_ve_attrs('role', 'text', 'Должность', 'team', $id); ?>><?php echo htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
        <?php if ($experience !== '' || $ve) { ?>
            <p class="team-card__exp"<?php echo site_ve_attrs('experience', 'text', 'Опыт', 'team', $id); ?>><?php echo htmlspecialchars($experience, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
        <?php if ($description !== '' || $ve) { ?>
            <div class="team-card__desc-wrap">
                <p
                    class="team-card__desc"
                    <?php echo site_ve_attrs('description', 'textarea', 'Описание', 'team', $id); ?>
                    <?php if ($expandable) { ?>
                        data-team-desc-short="<?php echo htmlspecialchars($preview['short'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-team-desc-full="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>"
                    <?php } ?>
                ><?php echo htmlspecialchars($expandable ? $preview['short'] : $description, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($expandable) { ?>
                    <button
                        type="button"
                        class="team-card__toggle"
                        data-team-toggle
                        aria-expanded="false"
                    >Подробнее</button>
                <?php } ?>
            </div>
        <?php } ?>
        <?php if ($member['telegram'] !== '' || $member['whatsapp'] !== '') { ?>
            <div class="team-card__contacts">
                <?php if ($member['telegram'] !== '') { ?>
                    <a class="team-card__contact" href="<?php echo htmlspecialchars($member['telegram'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Telegram</a>
                <?php } ?>
                <?php if ($member['whatsapp'] !== '') { ?>
                    <a class="team-card__contact" href="<?php echo htmlspecialchars($member['whatsapp'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                <?php } ?>
            </div>
        <?php } ?>
    </article>
    <?php
}

/**
 * @return array{short: string, expandable: bool}
 */
function site_team_description_preview(string $description, int $maxLen = 110): array
{
    $description = trim($description);
    if ($description === '') {
        return ['short' => '', 'expandable' => false];
    }
    if (mb_strlen($description) <= $maxLen) {
        return ['short' => $description, 'expandable' => false];
    }

    $cut = mb_substr($description, 0, $maxLen);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > (int) ($maxLen * 0.55)) {
        $cut = mb_substr($cut, 0, $space);
    }
    $cut = rtrim($cut, " \t.,;:—-");

    return ['short' => $cut . '…', 'expandable' => true];
}

/** Если в стаже одно число — показываем «N лет опыта». */
function site_team_format_experience(string $experience): string
{
    $experience = trim($experience);
    if ($experience === '') {
        return '';
    }
    if (preg_match('/^\d{1,2}$/', $experience) === 1) {
        $n = (int) $experience;

        return $n . ' ' . site_team_years_word($n) . ' опыта';
    }

    return $experience;
}

function site_team_years_word(int $n): string
{
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return 'лет';
    }
    if ($n1 === 1) {
        return 'год';
    }
    if ($n1 >= 2 && $n1 <= 4) {
        return 'года';
    }

    return 'лет';
}
