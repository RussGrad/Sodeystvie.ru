<?php

declare(strict_types=1);

function site_listing_description_inline_html(string $line): string
{
    $escaped = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
    $escaped = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $escaped) ?? $escaped;
    $escaped = preg_replace('/__(.+?)__/u', '<strong>$1</strong>', $escaped) ?? $escaped;

    return preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/u', '<em>$1</em>', $escaped) ?? $escaped;
}

function site_listing_description_is_heading_line(string $line): bool
{
    $line = trim($line);
    if ($line === '') {
        return false;
    }
    if (preg_match('/^\*\*(.+?)\*\*:?\s*$/u', $line) === 1) {
        return true;
    }
    if (preg_match('/^__(.+?)__:?\s*$/u', $line) === 1) {
        return true;
    }

    return preg_match('/:$/u', $line) === 1 && mb_strlen($line, 'UTF-8') <= 120;
}

function site_listing_description_heading_text(string $line): string
{
    $line = trim($line);
    if (preg_match('/^\*\*(.+?)\*\*:?\s*$/u', $line, $m) === 1) {
        return trim($m[1]);
    }
    if (preg_match('/^__(.+?)__:?\s*$/u', $line, $m) === 1) {
        return trim($m[1]);
    }

    return rtrim($line, ':');
}

function site_listing_description_is_list_line(string $line): bool
{
    return preg_match('/^(?:[-•*—–]|\d+[.)])\s+\S/u', trim($line)) === 1;
}

function site_listing_description_strip_list_marker(string $line): string
{
    return trim(preg_replace('/^(?:[-•*—–]|\d+[.)])\s*/u', '', trim($line)) ?? trim($line));
}

function site_listing_description_is_fragment_line(string $line): bool
{
    if (site_listing_description_is_list_line($line)) {
        return true;
    }
    $len = mb_strlen(trim($line), 'UTF-8');

    return $len > 0 && $len <= 150 && preg_match('/[.!?\x{2026}]$/u', trim($line)) !== 1;
}

/**
 * @param list<string> $lines
 * @return list<array{heading: ?string, lines: list<string>}>
 */
function site_listing_description_segment_lines(array $lines): array
{
    $segments = [];
    $current = ['heading' => null, 'lines' => []];

    foreach ($lines as $line) {
        if (site_listing_description_is_heading_line($line)) {
            if ($current['heading'] !== null || count($current['lines']) > 0) {
                $segments[] = $current;
            }
            $current = ['heading' => site_listing_description_heading_text($line), 'lines' => []];
            continue;
        }
        $current['lines'][] = $line;
    }

    if ($current['heading'] !== null || count($current['lines']) > 0) {
        $segments[] = $current;
    }

    return $segments;
}

/**
 * @param list<string> $lines
 */
function site_listing_description_render_list(array $lines): string
{
    $items = [];
    foreach ($lines as $line) {
        $line = site_listing_description_strip_list_marker($line);
        if ($line === '') {
            continue;
        }
        $items[] = '<li>' . site_listing_description_inline_html($line) . '</li>';
    }
    if ($items === []) {
        return '';
    }

    return '<ul class="listing-object__desc-list">' . implode('', $items) . '</ul>';
}

function site_listing_description_is_list_item_after_heading(string $line): bool
{
    if (site_listing_description_is_heading_line($line)) {
        return false;
    }
    if (mb_strlen($line, 'UTF-8') > 100) {
        return false;
    }
    if (preg_match('/\s[—–-]\s/u', $line) === 1) {
        return false;
    }

    return true;
}

/**
 * @param list<string> $lines
 */
function site_listing_description_render_segment_body(array $lines, bool $afterHeading): string
{
    if ($lines === []) {
        return '';
    }

    if ($afterHeading) {
        $listLines = [];
        $rest = [];
        foreach ($lines as $line) {
            if (count($rest) === 0 && site_listing_description_is_list_item_after_heading($line)) {
                $listLines[] = $line;
                continue;
            }
            $rest[] = $line;
        }

        if (count($listLines) >= 2) {
            $html = site_listing_description_render_list($listLines);
            if (count($rest) > 0) {
                $html .= "\n" . site_listing_description_render_body_lines($rest);
            }

            return $html;
        }
    }

    return site_listing_description_render_body_lines($lines);
}

/**
 * @param list<string> $lines
 */
function site_listing_description_render_body_lines(array $lines): string
{
    if ($lines === []) {
        return '';
    }

    $out = [];
    $count = count($lines);
    for ($i = 0; $i < $count; $i++) {
        $line = $lines[$i];
        $next = $lines[$i + 1] ?? null;
        $isFragment = site_listing_description_is_fragment_line($line);
        $nextIsFragment = $next !== null && site_listing_description_is_fragment_line($next);

        if ($isFragment && ($nextIsFragment || site_listing_description_is_list_line($line))) {
            $run = [];
            while ($i < $count && site_listing_description_is_fragment_line($lines[$i])
                && !site_listing_description_is_heading_line($lines[$i])) {
                $run[] = $lines[$i];
                $i++;
            }
            $i--;

            if (count($run) >= 2) {
                $out[] = site_listing_description_render_list($run);
            } else {
                $out[] = '<p>' . site_listing_description_inline_html($run[0]) . '</p>';
            }
            continue;
        }

        $out[] = '<p>' . site_listing_description_inline_html($line) . '</p>';
    }

    return implode("\n", $out);
}

function site_listing_object_description_html(?string $text): string
{
    $s = trim((string) $text);
    if ($s === '') {
        return '';
    }

    $s = preg_replace("/\r\n?/", "\n", $s) ?? $s;
    $s = preg_replace("/[ \t]+/u", ' ', $s) ?? $s;
    $s = preg_replace("/\n{3,}/", "\n\n", $s) ?? $s;

    $allLines = [];
    foreach (explode("\n", $s) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $allLines[] = $line;
        }
    }
    if ($allLines === []) {
        return '';
    }

    $html = [];
    foreach (site_listing_description_segment_lines($allLines) as $segment) {
        if ($segment['heading'] !== null && $segment['heading'] !== '') {
            $html[] = '<p class="listing-object__desc-subtitle"><strong>'
                . site_listing_description_inline_html($segment['heading'])
                . '</strong></p>';
        }
        $body = site_listing_description_render_segment_body(
            $segment['lines'],
            $segment['heading'] !== null && $segment['heading'] !== ''
        );
        if ($body !== '') {
            $html[] = $body;
        }
    }

    return implode("\n", $html);
}

function site_listing_description_html(?string $text, int $maxLen = 600): string
{
    $s = trim((string) $text);
    if ($s === '') {
        return '';
    }
    $s = preg_replace("/\r\n?/", "\n", $s) ?? $s;
    $s = preg_replace("/[ \t]+/u", ' ', $s) ?? $s;
    $s = preg_replace("/\n{3,}/", "\n\n", $s) ?? $s;
    if (mb_strlen($s, 'UTF-8') > $maxLen) {
        $s = rtrim(mb_substr($s, 0, $maxLen - 1, 'UTF-8')) . '…';
    }

    return nl2br(site_listing_description_inline_html($s), false);
}
