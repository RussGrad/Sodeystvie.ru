<?php

declare(strict_types=1);

/**
 * Отзывы клиентов: JSON в public/data/reviews.json + сводка из .env.
 */

function site_reviews_data_path(): string
{
    return dirname(__DIR__) . '/data/reviews.json';
}

/**
 * @return list<array{id: string, author: string, date: string, rating: int, text: string, source: string}>
 */
function site_reviews_all(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $path = site_reviews_data_path();
    if (!is_readable($path)) {
        $cache = [];

        return $cache;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        $cache = [];

        return $cache;
    }

    $decoded = json_decode($raw, true);
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
        $author = isset($row['author']) ? trim((string) $row['author']) : '';
        $text = isset($row['text']) ? trim((string) $row['text']) : '';
        if ($id === '' || $author === '' || $text === '') {
            continue;
        }
        $rating = isset($row['rating']) && is_numeric($row['rating']) ? (int) $row['rating'] : 5;
        $rating = max(1, min(5, $rating));
        $date = isset($row['date']) ? trim((string) $row['date']) : '';
        $source = isset($row['source']) ? trim((string) $row['source']) : 'yandex';

        $out[] = [
            'id' => $id,
            'author' => $author,
            'date' => $date,
            'rating' => $rating,
            'text' => $text,
            'source' => $source,
        ];
    }

    usort($out, static function (array $a, array $b): int {
        return strcmp($b['date'], $a['date']);
    });

    $cache = $out;

    return $cache;
}

/**
 * @return array{rating: float, count: int, countLabel: string, yandexUrl: string}
 */
function site_reviews_summary(): array
{
    $rating = (float) site_env('SITE_REVIEWS_RATING', '4.9');
    $rating = max(1.0, min(5.0, round($rating, 1)));
    $count = (int) site_env('SITE_REVIEWS_COUNT', '250');
    $count = max(0, $count);
    $countLabel = $count > 0
        ? number_format($count, 0, '.', ' ') . '+ отзывов'
        : 'отзывы клиентов';

    return [
        'rating' => $rating,
        'count' => $count,
        'countLabel' => $countLabel,
        'yandexUrl' => trim(site_env('SITE_YANDEX_ORG_URL', '')),
    ];
}

function site_reviews_source_label(string $source): string
{
    return match (strtolower(trim($source))) {
        '2gis', '2гис' => '2ГИС',
        'google' => 'Google',
        'avito', 'авито' => 'Авито',
        'domclick', 'домклик' => 'Домклик',
        default => 'Яндекс',
    };
}

/**
 * @return list<array{id: string, label: string, url: string, widgetSrc: string, cta: string}>
 */
function site_reviews_platforms(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $defs = [
        [
            'id' => 'yandex',
            'label' => 'Яндекс',
            'url' => trim(site_env('SITE_YANDEX_ORG_URL', '')),
            'widgetEnv' => 'SITE_YANDEX_REVIEWS_WIDGET_SRC',
            'cta' => 'Отзывы на Яндекс.Картах',
        ],
        [
            'id' => '2gis',
            'label' => '2ГИС',
            'url' => trim(site_env('SITE_2GIS_ORG_URL', '')),
            'widgetEnv' => 'SITE_2GIS_REVIEWS_WIDGET_SRC',
            'cta' => 'Отзывы в 2ГИС',
        ],
        [
            'id' => 'domclick',
            'label' => 'Домклик',
            'url' => trim(site_env('SITE_DOMCLICK_REVIEWS_URL', '')),
            'widgetEnv' => 'SITE_DOMCLICK_REVIEWS_WIDGET_SRC',
            'cta' => 'Отзывы на Домклик',
        ],
        [
            'id' => 'avito',
            'label' => 'Авито',
            'url' => trim(site_env('SITE_AVITO_REVIEWS_URL', '')),
            'widgetEnv' => 'SITE_AVITO_REVIEWS_WIDGET_SRC',
            'cta' => 'Отзывы на Авито',
        ],
    ];

    $out = [];
    foreach ($defs as $def) {
        $url = $def['url'];
        if ($url === '' || !site_reviews_is_safe_external_url($url)) {
            continue;
        }

        $widgetSrc = trim(site_env($def['widgetEnv'], ''));
        if ($widgetSrc === '' && $def['id'] === 'yandex') {
            $widgetSrc = site_reviews_yandex_widget_from_org($url);
        }
        if ($widgetSrc !== '' && !site_reviews_is_safe_widget_src($widgetSrc)) {
            $widgetSrc = '';
        }

        $out[] = [
            'id' => $def['id'],
            'label' => $def['label'],
            'url' => $url,
            'widgetSrc' => $widgetSrc,
            'cta' => $def['cta'],
        ];
    }

    $cache = $out;

    return $cache;
}

function site_reviews_is_safe_external_url(string $url): bool
{
    $parts = parse_url($url);
    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https') {
        return false;
    }
    $host = strtolower((string) ($parts['host'] ?? ''));

    return in_array($host, site_reviews_allowed_hosts(), true);
}

function site_reviews_is_safe_widget_src(string $src): bool
{
    return site_reviews_is_safe_external_url($src);
}

/**
 * @return list<string>
 */
function site_reviews_allowed_hosts(): array
{
    return [
        'yandex.ru',
        'yandex.com',
        'yandex.kz',
        '2gis.ru',
        '2gis.com',
        'widgets.2gis.com',
        'domclick.ru',
        'avito.ru',
        'www.avito.ru',
        'm.avito.ru',
    ];
}

function site_reviews_yandex_widget_from_org(string $orgUrl): string
{
    if (preg_match('#/org/[^/]+/(\d+)#', $orgUrl, $m) === 1) {
        return 'https://yandex.ru/maps-reviews-widget/' . $m[1] . '?comments';
    }
    if (preg_match('#maps-reviews-widget/(\d+)#', $orgUrl, $m) === 1) {
        return 'https://yandex.ru/maps-reviews-widget/' . $m[1] . '?comments';
    }

    return '';
}

function site_reviews_platforms_with_widgets(): array
{
    return array_values(array_filter(
        site_reviews_platforms(),
        static fn (array $p): bool => ($p['widgetSrc'] ?? '') !== ''
    ));
}

function site_render_review_platform_icon(string $platformId): void
{
    $id = strtolower(trim($platformId));
    echo '<span class="review-platform__icon" aria-hidden="true">';
    if ($id === 'yandex') {
        echo '<svg viewBox="0 0 24 24" width="28" height="28"><circle cx="12" cy="12" r="12" fill="#FC3F1D"/><text x="12" y="16" text-anchor="middle" fill="#fff" font-size="13" font-weight="700" font-family="Arial,sans-serif">Я</text></svg>';
    } elseif ($id === '2gis') {
        echo '<svg viewBox="0 0 24 24" width="28" height="28"><circle cx="12" cy="12" r="12" fill="#2E8B57"/><text x="12" y="16" text-anchor="middle" fill="#fff" font-size="11" font-weight="700" font-family="Arial,sans-serif">2</text></svg>';
    } elseif ($id === 'domclick') {
        echo '<svg viewBox="0 0 24 24" width="28" height="28"><circle cx="12" cy="12" r="12" fill="#0066FF"/><text x="12" y="16" text-anchor="middle" fill="#fff" font-size="10" font-weight="700" font-family="Arial,sans-serif">Д</text></svg>';
    } elseif ($id === 'avito') {
        echo '<svg viewBox="0 0 24 24" width="28" height="28"><circle cx="12" cy="12" r="12" fill="#00AAFF"/><text x="12" y="16" text-anchor="middle" fill="#fff" font-size="11" font-weight="700" font-family="Arial,sans-serif">А</text></svg>';
    } else {
        echo '<svg viewBox="0 0 24 24" width="28" height="28"><circle cx="12" cy="12" r="12" fill="#888"/></svg>';
    }
    echo '</span>';
}

/**
 * @param list<array{id: string, label: string, url: string, widgetSrc: string, cta: string}> $platforms
 */
function site_render_review_platform_links(array $platforms, string $listClass = 'review-platforms'): void
{
    if (count($platforms) === 0) {
        return;
    }
    ?>
    <ul class="<?php echo htmlspecialchars($listClass, ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ($platforms as $platform) {
            $mod = ' review-platform--' . preg_replace('/[^a-z0-9\-]/', '', $platform['id']);
            ?>
            <li class="review-platforms__item">
                <a
                    class="review-platform<?php echo htmlspecialchars($mod, ENT_QUOTES, 'UTF-8'); ?>"
                    href="<?php echo htmlspecialchars($platform['url'], ENT_QUOTES, 'UTF-8'); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <?php site_render_review_platform_icon($platform['id']); ?>
                    <span class="review-platform__body">
                        <span class="review-platform__name"><?php echo htmlspecialchars($platform['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="review-platform__cta"><?php echo htmlspecialchars($platform['cta'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                </a>
            </li>
        <?php } ?>
    </ul>
    <?php
}

/**
 * @param list<array{id: string, label: string, url: string, widgetSrc: string, cta: string}> $platforms
 */
function site_render_review_platform_widgets(array $platforms): void
{
    if (count($platforms) === 0) {
        return;
    }
    ?>
    <div class="review-widgets">
        <?php foreach ($platforms as $platform) {
            if (($platform['widgetSrc'] ?? '') === '') {
                continue;
            }
            $title = $platform['label'] . ' — отзывы';
            ?>
            <section class="review-widgets__item" aria-labelledby="review-widget-<?php echo htmlspecialchars($platform['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <header class="review-widgets__head">
                    <h2 class="review-widgets__title" id="review-widget-<?php echo htmlspecialchars($platform['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($platform['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <a class="review-widgets__open" href="<?php echo htmlspecialchars($platform['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Открыть на сайте</a>
                </header>
                <div class="review-widgets__frame-wrap">
                    <iframe
                        class="review-widgets__frame"
                        src="<?php echo htmlspecialchars($platform['widgetSrc'], ENT_QUOTES, 'UTF-8'); ?>"
                        title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                </div>
            </section>
        <?php } ?>
    </div>
    <?php
}

function site_reviews_format_date(?string $iso): string
{
    $s = trim((string) $iso);
    if ($s === '') {
        return '';
    }
    $ts = strtotime($s);
    if ($ts === false) {
        return $s;
    }
    $months = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
    ];
    $m = (int) date('n', $ts);
    $month = $months[$m] ?? date('m', $ts);

    return (int) date('j', $ts) . ' ' . $month . ' ' . date('Y', $ts);
}

function site_reviews_render_stars(int $rating, string $class = 'review-stars'): string
{
    $rating = max(1, min(5, $rating));
    $html = '<span class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" aria-label="Оценка ' . $rating . ' из 5">';
    for ($i = 1; $i <= 5; $i++) {
        $filled = $i <= $rating ? ' is-filled' : '';
        $html .= '<svg class="review-stars__icon' . $filled . '" viewBox="0 0 20 20" aria-hidden="true">'
            . '<path d="M10 1.5l2.47 5.01 5.53.8-4 3.9.94 5.5L10 14.9l-4.94 2.6.94-5.5-4-3.9 5.53-.8L10 1.5z"/>'
            . '</svg>';
    }
    $html .= '</span>';

    return $html;
}

/**
 * @param array{id: string, author: string, date: string, rating: int, text: string, source: string} $review
 */
function site_render_review_card(array $review, bool $compact = false): void
{
    $cardClass = 'review-card' . ($compact ? ' review-card--compact' : '');
    ?>
    <article class="<?php echo $cardClass; ?>">
        <?php echo site_reviews_render_stars($review['rating']); ?>
        <blockquote class="review-card__text">
            <p><?php echo htmlspecialchars($review['text'], ENT_QUOTES, 'UTF-8'); ?></p>
        </blockquote>
        <footer class="review-card__footer">
            <cite class="review-card__author"><?php echo htmlspecialchars($review['author'], ENT_QUOTES, 'UTF-8'); ?></cite>
            <?php if ($review['date'] !== '') { ?>
                <time class="review-card__date" datetime="<?php echo htmlspecialchars($review['date'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars(site_reviews_format_date($review['date']), ENT_QUOTES, 'UTF-8'); ?>
                </time>
            <?php } ?>
            <span class="review-card__source"><?php echo htmlspecialchars(site_reviews_source_label($review['source']), ENT_QUOTES, 'UTF-8'); ?></span>
        </footer>
    </article>
    <?php
}
