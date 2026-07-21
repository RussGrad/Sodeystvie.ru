<?php

declare(strict_types=1);

/**
 * Кейсы: public/data/cases.json + фото public/assets/cases/{id}.*
 */

require_once __DIR__ . '/site-image.php';

function site_cases_data_path(): string
{
    return dirname(__DIR__) . '/data/cases.json';
}

function site_case_image_path(string $id): ?string
{
    $safe = preg_replace('/[^a-z0-9_-]/i', '', $id) ?? '';
    if ($safe === '') {
        return null;
    }

    $dir = dirname(__DIR__) . '/assets/cases';
    $webBase = '/assets/cases/' . $safe;

    foreach (['.jpg', '.jpeg', '.png', '.webp'] as $ext) {
        if (is_file($dir . '/' . $safe . $ext)) {
            return $webBase . $ext;
        }
    }

    return null;
}

/**
 * @return list<array{id: string, tag: string, title: string, result: string, text: string, image: string}>
 */
function site_cases_all(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $path = site_cases_data_path();
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
        $title = isset($row['title']) ? trim((string) $row['title']) : '';
        if ($id === '' || $title === '') {
            continue;
        }

        $image = isset($row['image']) ? trim((string) $row['image']) : '';
        if ($image === '') {
            $image = site_case_image_path($id) ?? '';
        } elseif (!preg_match('#^https?://#i', $image) && !str_starts_with($image, '/')) {
            $image = '';
        } elseif (str_starts_with($image, '/') && !is_readable(dirname(__DIR__) . $image)) {
            $image = site_case_image_path($id) ?? '';
        }

        $out[] = [
            'id' => $id,
            'tag' => isset($row['tag']) ? trim((string) $row['tag']) : '',
            'title' => $title,
            'result' => isset($row['result']) ? trim((string) $row['result']) : '',
            'text' => isset($row['text']) ? trim((string) $row['text']) : '',
            'image' => $image,
        ];
    }

    $cache = $out;

    return $cache;
}

/**
 * @param array{id: string, tag: string, title: string, result: string, text: string, image?: string} $case
 */
function site_render_case_card(array $case): void
{
    if (!function_exists('site_ve_attrs')) {
        require_once __DIR__ . '/visual-editor.php';
    }

    $id = (string) ($case['id'] ?? '');
    $image = trim((string) ($case['image'] ?? ''));
    $veOn = site_visual_editor_enabled();
    $showMedia = $image !== '' || $veOn;
    ?>
    <article class="case-card">
        <?php if ($showMedia) { ?>
            <div
                class="case-card__media<?php echo $image === '' ? ' case-card__media--empty' : ''; ?>"
                <?php
                echo site_ve_attrs('image', 'image', 'Фото кейса', 'cases', $id);
                if ($image !== '') {
                    echo ' data-ve-has-image="1"';
                }
                ?>
            >
                <?php if ($image !== '') {
                    if (preg_match('#^https?://#i', $image)) { ?>
                        <img
                            class="case-card__photo"
                            src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>"
                            alt=""
                            width="640"
                            height="360"
                            loading="lazy"
                            decoding="async"
                        >
                    <?php } else {
                        echo site_render_static_picture(
                            $image,
                            '',
                            'case-card__photo',
                            'width="640" height="360"'
                        );
                    }
                } elseif ($veOn) { ?>
                    <span class="case-card__media-hint">Добавить фото</span>
                <?php } ?>
            </div>
        <?php } ?>
        <div class="case-card__body">
            <?php if ($case['tag'] !== '' || $veOn) { ?>
                <span class="case-card__tag"<?php echo site_ve_attrs('tag', 'text', 'Тег кейса', 'cases', $id); ?>><?php echo htmlspecialchars($case['tag'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php } ?>
            <h3 class="case-card__title"<?php echo site_ve_attrs('title', 'text', 'Заголовок кейса', 'cases', $id); ?>><?php echo htmlspecialchars($case['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php if ($case['result'] !== '' || $veOn) { ?>
                <p class="case-card__result"<?php echo site_ve_attrs('result', 'text', 'Результат', 'cases', $id); ?>><?php echo htmlspecialchars($case['result'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>
            <?php if ($case['text'] !== '' || $veOn) { ?>
                <p class="case-card__text"<?php echo site_ve_attrs('text', 'textarea', 'Текст кейса', 'cases', $id); ?>><?php echo htmlspecialchars($case['text'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>
        </div>
    </article>
    <?php
}
