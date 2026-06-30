<?php

declare(strict_types=1);

/**
 * Отзывы клиентов: public/data/reviews-domclick.json, public/data/reviews.json + сводка.
 */

function site_reviews_data_path(): string
{
    return dirname(__DIR__) . '/data/reviews.json';
}

function site_reviews_domclick_data_path(): string
{
    return site_reviews_external_data_path('domclick');
}

/**
 * @return list<string>
 */
function site_reviews_platform_ids(): array
{
    return ['domclick', 'yandex', '2gis', 'avito'];
}

function site_reviews_external_data_path(string $platformId): string
{
    $id = preg_replace('/[^a-z0-9_-]/', '', strtolower($platformId));

    return dirname(__DIR__) . '/data/reviews-' . $id . '.json';
}

function site_reviews_platforms_meta_path(): string
{
    return dirname(__DIR__) . '/data/reviews-platforms.json';
}

/**
 * @return array{summary: ?array{rating: ?float, count: ?int, title: ?string}, reviews: list<array<string, mixed>>, sourceUrl: ?string}
 */
function site_reviews_external_pack(string $platformId): array
{
    static $cache = [];
    if (isset($cache[$platformId])) {
        return $cache[$platformId];
    }

    $empty = ['summary' => null, 'reviews' => [], 'sourceUrl' => null];
    if (!in_array($platformId, site_reviews_platform_ids(), true)) {
        $cache[$platformId] = $empty;

        return $cache[$platformId];
    }

    $path = site_reviews_external_data_path($platformId);
    if (!is_readable($path)) {
        $cache[$platformId] = $empty;

        return $cache[$platformId];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        $cache[$platformId] = $empty;

        return $cache[$platformId];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $cache[$platformId] = $empty;

        return $cache[$platformId];
    }

    $summary = null;
    if (isset($decoded['summary']) && is_array($decoded['summary'])) {
        $s = $decoded['summary'];
        $summary = [
            'rating' => isset($s['rating']) && is_numeric($s['rating']) ? (float) $s['rating'] : null,
            'count' => isset($s['count']) && is_numeric($s['count']) ? (int) $s['count'] : null,
            'title' => isset($s['title']) && is_string($s['title']) && trim($s['title']) !== ''
                ? trim($s['title'])
                : null,
        ];
    }

    $reviews = [];
    foreach ($decoded['reviews'] ?? [] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $normalized = site_reviews_normalize_row($row, $platformId);
        if ($normalized !== null) {
            $reviews[] = $normalized;
        }
    }

    usort($reviews, static function (array $a, array $b): int {
        return strcmp($b['date'], $a['date']);
    });

    $sourceUrl = isset($decoded['sourceUrl']) && is_string($decoded['sourceUrl'])
        ? trim($decoded['sourceUrl'])
        : null;
    if ($sourceUrl === '') {
        $sourceUrl = null;
    }

    $cache[$platformId] = ['summary' => $summary, 'reviews' => $reviews, 'sourceUrl' => $sourceUrl];

    return $cache[$platformId];
}

/**
 * @return array{summary: ?array{rating: ?float, count: ?int, title: ?string}, reviews: list<array<string, mixed>>, sourceUrl: ?string}
 */
function site_reviews_domclick_pack(): array
{
    return site_reviews_external_pack('domclick');
}

/**
 * @param array<string, mixed> $row
 * @return ?array{id: string, author: string, date: string, rating: int, text: string, source: string, demo: bool, reply: ?array{author: ?string, text: string, date: ?string}}
 */
function site_reviews_normalize_row(array $row, string $defaultSource = 'yandex'): ?array
{
    $id = isset($row['id']) ? trim((string) $row['id']) : '';
    $author = isset($row['author']) ? trim((string) $row['author']) : '';
    $text = isset($row['text']) ? trim((string) $row['text']) : '';
    if ($id === '' || $author === '' || $text === '') {
        return null;
    }
    $rating = isset($row['rating']) && is_numeric($row['rating']) ? (int) $row['rating'] : 5;
    $rating = max(1, min(5, $rating));
    $date = isset($row['date']) ? trim((string) $row['date']) : '';
    $source = isset($row['source']) && trim((string) $row['source']) !== ''
        ? trim((string) $row['source'])
        : $defaultSource;
    $demo = !empty($row['demo']);

    $reply = null;
    if (isset($row['reply']) && is_array($row['reply'])) {
        $replyText = isset($row['reply']['text']) ? trim((string) $row['reply']['text']) : '';
        if ($replyText !== '') {
            $reply = [
                'author' => isset($row['reply']['author']) ? trim((string) $row['reply']['author']) : null,
                'text' => $replyText,
                'date' => isset($row['reply']['date']) ? trim((string) $row['reply']['date']) : null,
            ];
        }
    }

    return [
        'id' => $id,
        'author' => $author,
        'date' => $date,
        'rating' => $rating,
        'text' => $text,
        'source' => $source,
        'demo' => $demo,
        'reply' => $reply,
    ];
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

    $out = [];
    foreach (site_reviews_platform_ids() as $platformId) {
        foreach (site_reviews_external_pack($platformId)['reviews'] as $review) {
            $out[] = $review;
        }
    }

    $path = site_reviews_data_path();
    if (is_readable($path)) {
        $raw = file_get_contents($path);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    if (!empty($row['demo'])) {
                        continue;
                    }
                    $normalized = site_reviews_normalize_row($row);
                    if ($normalized !== null) {
                        $out[] = $normalized;
                    }
                }
            }
        }
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
    $all = site_reviews_all();
    $domclick = site_reviews_domclick_pack()['summary'];

    $rating = null;
    $count = null;

    if (is_array($domclick)) {
        if (isset($domclick['rating']) && is_numeric($domclick['rating'])) {
            $rating = (float) $domclick['rating'];
        }
        if (isset($domclick['count']) && is_numeric($domclick['count'])) {
            $count = (int) $domclick['count'];
        }
    }

    if ($rating === null && count($all) > 0) {
        $sum = 0;
        foreach ($all as $review) {
            $sum += (int) ($review['rating'] ?? 5);
        }
        $rating = round($sum / count($all), 1);
    }
    if ($count === null) {
        $count = count($all);
    }

    if ($rating === null) {
        $ratingRaw = site_content_setting('reviews_rating', '');
        $rating = $ratingRaw !== '' ? (float) $ratingRaw : null;
        if ($rating === null) {
            $envRating = trim(site_env('SITE_REVIEWS_RATING', ''));
            $rating = $envRating !== '' ? (float) $envRating : null;
        }
    }
    if ($count === null || $count === 0) {
        $countRaw = site_content_setting('reviews_count', '');
        $count = $countRaw !== '' ? (int) $countRaw : null;
        if ($count === null) {
            $envCount = trim(site_env('SITE_REVIEWS_COUNT', ''));
            $count = $envCount !== '' ? (int) $envCount : 0;
        }
    }

    $rating = $rating !== null ? max(1.0, min(5.0, round($rating, 1))) : null;
    $count = max(0, (int) ($count ?? 0));

    $countLabel = $count > 0
        ? number_format($count, 0, '.', ' ') . ' оценок'
        : (count($all) > 0 ? count($all) . ' отзывов на сайте' : 'отзывы клиентов');
    if (site_reviews_all_demo()) {
        $countLabel = 'проверочные карточки';
    }

    return [
        'rating' => $rating,
        'count' => $count,
        'countLabel' => $countLabel,
        'yandexUrl' => trim(site_env('SITE_YANDEX_ORG_URL', '')),
        'isDemo' => site_reviews_all_demo(),
        'hasRating' => $rating !== null && $count > 0,
    ];
}

function site_reviews_preview_limit(): int
{
    $raw = trim(site_env('SITE_REVIEWS_PREVIEW_LIMIT', '3'));
    if ($raw === '') {
        return 3;
    }

    return max(2, min(6, (int) $raw));
}

/**
 * @param ?list<array<string, mixed>> $reviews
 * @return list<array<string, mixed>>
 */
function site_reviews_preview(?array $reviews = null): array
{
    $all = $reviews ?? site_reviews_all();

    return array_slice($all, 0, site_reviews_preview_limit());
}

/**
 * @return ?array{url: string, label: string, moreCount: int, cta: string}
 * @deprecated Используйте site_reviews_platform_more_ctas()
 */
function site_reviews_more_on_source(): ?array
{
    $ctas = site_reviews_platform_more_ctas();

    return count($ctas) > 0 ? $ctas[0] : null;
}

/**
 * @return array<string, int>
 */
function site_reviews_shown_count_by_platform(?array $reviews = null): array
{
    $counts = array_fill_keys(site_reviews_platform_ids(), 0);
    foreach (site_reviews_preview($reviews) as $review) {
        $platformId = site_reviews_source_platform_id((string) ($review['source'] ?? ''));
        if (isset($counts[$platformId])) {
            $counts[$platformId]++;
        }
    }

    return $counts;
}

function site_reviews_platform_count_env_key(string $platformId): string
{
    return match ($platformId) {
        'yandex' => 'SITE_YANDEX_REVIEWS_COUNT',
        '2gis' => 'SITE_2GIS_REVIEWS_COUNT',
        'domclick' => 'SITE_DOMCLICK_REVIEWS_COUNT',
        'avito' => 'SITE_AVITO_REVIEWS_COUNT',
        default => '',
    };
}

function site_reviews_platform_total_count(string $platformId): int
{
    $pack = site_reviews_external_pack($platformId);
    $fromPack = isset($pack['summary']['count']) && is_numeric($pack['summary']['count'])
        ? (int) $pack['summary']['count']
        : 0;
    $fromReviews = count($pack['reviews']);
    $total = max($fromPack, $fromReviews);

    $envKey = site_reviews_platform_count_env_key($platformId);
    if ($envKey !== '') {
        $envRaw = trim(site_env($envKey, ''));
        if ($envRaw !== '' && is_numeric($envRaw)) {
            $total = max($total, (int) $envRaw);
        }
    }

    static $meta = null;
    if ($meta === null) {
        $meta = [];
        $path = site_reviews_platforms_meta_path();
        if (is_readable($path)) {
            $raw = file_get_contents($path);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
        }
    }

    if (isset($meta[$platformId]) && is_array($meta[$platformId])) {
        $metaCount = $meta[$platformId]['count'] ?? null;
        if (is_numeric($metaCount)) {
            $total = max($total, (int) $metaCount);
        }
    }

    return max(0, $total);
}

function site_reviews_platform_more_cta_text(string $platformId, string $label, int $shownCount, int $totalCount): string
{
    $more = $totalCount > 0 ? max(0, $totalCount - $shownCount) : 0;

    if ($shownCount > 0 && $more > 0) {
        return $more === 1
            ? 'Ещё 1 отзыв на ' . $label
            : 'Ещё ' . number_format($more, 0, '.', ' ') . ' отзывов на ' . $label;
    }

    if ($totalCount > 0) {
        return $totalCount === 1
            ? '1 отзыв на ' . $label
            : number_format($totalCount, 0, '.', ' ') . ' отзывов на ' . $label;
    }

    return 'Читать отзывы на ' . $label;
}

/**
 * @return list<array{id: string, url: string, label: string, cta: string, moreCount: int}>
 */
function site_reviews_platform_more_ctas(): array
{
    $shown = site_reviews_shown_count_by_platform();
    $ctas = [];

    foreach (site_reviews_platforms() as $platform) {
        $platformId = (string) $platform['id'];
        $total = site_reviews_platform_total_count($platformId);
        $shownCount = $shown[$platformId] ?? 0;
        $more = $total > 0 ? max(0, $total - $shownCount) : 0;

        $ctas[] = [
            'id' => $platformId,
            'url' => $platform['url'],
            'label' => $platform['label'],
            'cta' => site_reviews_platform_more_cta_text($platformId, $platform['label'], $shownCount, $total),
            'moreCount' => $more,
        ];
    }

    return $ctas;
}

function site_reviews_has_platform_ctas(): bool
{
    return count(site_reviews_platform_more_ctas()) > 0;
}

function site_render_reviews_platform_ctas(?string $wrapClass = 'reviews__actions'): void
{
    $ctas = site_reviews_platform_more_ctas();
    if (count($ctas) === 0) {
        return;
    }

    $links = '';
    foreach ($ctas as $cta) {
        $mod = ' reviews__source-link--' . preg_replace('/[^a-z0-9_-]/', '', $cta['id']);
        $links .= '<a'
            . ' class="reviews__source-link' . htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') . '"'
            . ' href="' . htmlspecialchars($cta['url'], ENT_QUOTES, 'UTF-8') . '"'
            . ' rel="noopener noreferrer"'
            . '>' . htmlspecialchars($cta['cta'], ENT_QUOTES, 'UTF-8') . '</a>';
    }

    if ($wrapClass === null || $wrapClass === '') {
        echo $links;

        return;
    }

    echo '<div class="' . htmlspecialchars($wrapClass, ENT_QUOTES, 'UTF-8') . '">' . $links . '</div>';
}

/**
 * @deprecated Используйте site_render_reviews_platform_ctas()
 */
function site_render_reviews_source_cta(?string $wrapClass = 'reviews__actions'): void
{
    site_render_reviews_platform_ctas($wrapClass);
}

function site_reviews_all_demo(): bool
{
    $all = site_reviews_all();
    if (count($all) === 0) {
        return false;
    }
    foreach ($all as $review) {
        if (empty($review['demo'])) {
            return false;
        }
    }

    return true;
}

function site_reviews_has_demo(): bool
{
    foreach (site_reviews_all() as $review) {
        if (!empty($review['demo'])) {
            return true;
        }
    }

    return false;
}

function site_render_reviews_demo_notice(): void
{
    if (!site_reviews_has_demo()) {
        return;
    }
    ?>
    <p class="reviews-demo-notice" role="status">
        Показаны <strong>проверочные</strong> карточки. Замените содержимое в <code>public/data/reviews.json</code> на реальные отзывы.
    </p>
    <?php
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

function site_reviews_source_platform_id(string $source): string
{
    return match (strtolower(trim($source))) {
        '2gis', '2гис' => '2gis',
        'google' => 'google',
        'avito', 'авито' => 'avito',
        'domclick', 'домклик' => 'domclick',
        default => 'yandex',
    };
}

/**
 * @return array<string, string>
 */
function site_reviews_platform_urls_map(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = [
        'yandex' => trim(site_env('SITE_YANDEX_ORG_URL', '')),
        '2gis' => trim(site_env('SITE_2GIS_ORG_URL', '')),
        'domclick' => trim(site_env('SITE_DOMCLICK_REVIEWS_URL', '')),
        'avito' => trim(site_env('SITE_AVITO_REVIEWS_URL', '')),
    ];

    foreach (site_reviews_platform_ids() as $platformId) {
        $packUrl = site_reviews_external_pack($platformId)['sourceUrl'] ?? null;
        if (
            ($cache[$platformId] === '' || !site_reviews_is_safe_external_url($cache[$platformId]))
            && is_string($packUrl)
            && $packUrl !== ''
            && site_reviews_is_safe_external_url($packUrl)
        ) {
            $cache[$platformId] = $packUrl;
        }
    }

    $metaPath = site_reviews_platforms_meta_path();
    if (is_readable($metaPath)) {
        $raw = file_get_contents($metaPath);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach (site_reviews_platform_ids() as $platformId) {
                    if ($cache[$platformId] !== '' && site_reviews_is_safe_external_url($cache[$platformId])) {
                        continue;
                    }
                    $metaUrl = $decoded[$platformId]['sourceUrl'] ?? null;
                    if (is_string($metaUrl) && $metaUrl !== '' && site_reviews_is_safe_external_url($metaUrl)) {
                        $cache[$platformId] = $metaUrl;
                    }
                }
            }
        }
    }

    return $cache;
}

function site_reviews_source_url(string $source, ?string $overrideUrl = null): string
{
    if (is_string($overrideUrl) && $overrideUrl !== '' && site_reviews_is_safe_external_url($overrideUrl)) {
        return $overrideUrl;
    }

    $urls = site_reviews_platform_urls_map();
    $platformId = site_reviews_source_platform_id($source);
    $url = $urls[$platformId] ?? '';
    if ($url !== '' && site_reviews_is_safe_external_url($url)) {
        return $url;
    }

    return '';
}

function site_render_review_source_badge(string $source, ?string $overrideUrl = null): void
{
    $label = site_reviews_source_label($source);
    $url = site_reviews_source_url($source, $overrideUrl);
    if ($url !== '') {
        $title = 'Открыть отзывы на ' . $label;
        ?>
        <a
            class="review-card__source review-card__source--link"
            href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
            rel="noopener noreferrer"
            title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
            aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
        ><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
        <?php

        return;
    }
    ?>
    <span class="review-card__source"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
    <?php
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
            'widgetEnv' => 'SITE_YANDEX_REVIEWS_WIDGET_SRC',
            'cta' => 'Отзывы на Яндекс.Картах',
        ],
        [
            'id' => '2gis',
            'label' => '2ГИС',
            'widgetEnv' => 'SITE_2GIS_REVIEWS_WIDGET_SRC',
            'cta' => 'Отзывы в 2ГИС',
        ],
        [
            'id' => 'domclick',
            'label' => 'Домклик',
            'widgetEnv' => 'SITE_DOMCLICK_REVIEWS_WIDGET_SRC',
            'cta' => 'Отзывы на Домклик',
        ],
        [
            'id' => 'avito',
            'label' => 'Авито',
            'widgetEnv' => 'SITE_AVITO_REVIEWS_WIDGET_SRC',
            'cta' => 'Отзывы на Авито',
        ],
    ];

    $urls = site_reviews_platform_urls_map();
    $out = [];
    foreach ($defs as $def) {
        $url = $urls[$def['id']] ?? '';
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
        'agencies.domclick.ru',
        'irkutsk.domclick.ru',
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
    $isDemo = !empty($review['demo']);
    $cardClass = 'review-card'
        . ($compact ? ' review-card--compact' : '')
        . ($isDemo ? ' review-card--demo' : '');
    ?>
    <article class="<?php echo $cardClass; ?>">
        <?php if ($isDemo) { ?>
            <span class="review-card__demo-badge">Демо</span>
        <?php } ?>
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
            <?php
            $reviewSourceUrl = isset($review['sourceUrl']) ? trim((string) $review['sourceUrl']) : '';
            site_render_review_source_badge($review['source'], $reviewSourceUrl !== '' ? $reviewSourceUrl : null);
            ?>
        </footer>
        <?php
        $reply = isset($review['reply']) && is_array($review['reply']) ? $review['reply'] : null;
        if (is_array($reply) && !empty($reply['text']) && !$compact) {
            $replyAuthor = isset($reply['author']) ? trim((string) $reply['author']) : '';
            $replyDate = isset($reply['date']) ? trim((string) $reply['date']) : '';
            ?>
            <div class="review-card__reply">
                <?php if ($replyAuthor !== '') { ?>
                    <strong class="review-card__reply-author"><?php echo htmlspecialchars($replyAuthor, ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php } ?>
                <?php if ($replyDate !== '') { ?>
                    <time class="review-card__reply-date" datetime="<?php echo htmlspecialchars($replyDate, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars(site_reviews_format_date($replyDate), ENT_QUOTES, 'UTF-8'); ?>
                    </time>
                <?php } ?>
                <p class="review-card__reply-text"><?php echo nl2br(htmlspecialchars((string) $reply['text'], ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
        <?php } ?>
    </article>
    <?php
}
