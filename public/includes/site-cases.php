<?php

declare(strict_types=1);

/**
 * Кейсы: public/data/cases.json
 */

function site_cases_data_path(): string
{
    return dirname(__DIR__) . '/data/cases.json';
}

/**
 * @return list<array{id: string, tag: string, title: string, result: string, text: string}>
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

        $out[] = [
            'id' => $id,
            'tag' => isset($row['tag']) ? trim((string) $row['tag']) : '',
            'title' => $title,
            'result' => isset($row['result']) ? trim((string) $row['result']) : '',
            'text' => isset($row['text']) ? trim((string) $row['text']) : '',
        ];
    }

    $cache = $out;

    return $cache;
}

/**
 * @param array{id: string, tag: string, title: string, result: string, text: string} $case
 */
function site_render_case_card(array $case): void
{
    ?>
    <article class="case-card">
        <?php if ($case['tag'] !== '') { ?>
            <span class="case-card__tag"><?php echo htmlspecialchars($case['tag'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php } ?>
        <h3 class="case-card__title"><?php echo htmlspecialchars($case['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <?php if ($case['result'] !== '') { ?>
            <p class="case-card__result"><?php echo htmlspecialchars($case['result'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
        <?php if ($case['text'] !== '') { ?>
            <p class="case-card__text"><?php echo htmlspecialchars($case['text'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
    </article>
    <?php
}
