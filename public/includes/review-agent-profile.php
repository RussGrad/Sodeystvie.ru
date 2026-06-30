<?php

declare(strict_types=1);

/**
 * Профиль риелтора в стиле ДомКлик (страница /reviews/).
 */
$platforms = site_reviews_platforms();
site_render_reviews_agent_profile($platforms);
