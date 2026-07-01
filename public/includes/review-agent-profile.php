<?php

declare(strict_types=1);

/**
 * Дашборд отзывов: агентство, команда и сводка по площадкам.
 */
$platforms = site_reviews_platforms();
site_render_reviews_dashboard($platforms);
