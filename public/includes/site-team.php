<?php

declare(strict_types=1);

require_once __DIR__ . '/site-image.php';

/**
 * Команда: public/data/team.json, фото — public/assets/team/{id}.jpg
 */

function site_team_data_path(): string
{
    return dirname(__DIR__) . '/data/team.json';
}

/**
 * @return list<array{id: string, name: string, role: string, experience: string, photo: string, telegram: string, whatsapp: string}>
 */
function site_team_all(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $path = site_team_data_path();
    if (!is_readable($path)) {
        $cache = [];

        return $cache;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        $cache = [];

        return $cache;
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
            'photo' => $photo,
            'telegram' => isset($row['telegram']) ? trim((string) $row['telegram']) : '',
            'whatsapp' => isset($row['whatsapp']) ? trim((string) $row['whatsapp']) : '',
        ];
    }

    $cache = $out;

    return $cache;
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
 * @param array{id: string, name: string, role: string, experience: string, photo: string, telegram: string, whatsapp: string} $member
 */
function site_render_team_card(array $member): void
{
    $hasPhoto = $member['photo'] !== '' && is_readable(dirname(__DIR__) . $member['photo']);
    ?>
    <article class="team-card">
        <div class="team-card__photo-wrap">
            <?php if ($hasPhoto) {
                echo site_render_static_picture(
                    $member['photo'],
                    $member['name'],
                    'team-card__photo'
                );
            } else { ?>
                <span class="team-card__initials" aria-hidden="true"><?php echo htmlspecialchars(site_team_initials($member['name']), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php } ?>
        </div>
        <h3 class="team-card__name"><?php echo htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <?php if ($member['role'] !== '') { ?>
            <p class="team-card__role"><?php echo htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
        <?php if ($member['experience'] !== '') { ?>
            <p class="team-card__exp"><?php echo htmlspecialchars($member['experience'], ENT_QUOTES, 'UTF-8'); ?></p>
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
