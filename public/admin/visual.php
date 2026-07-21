<?php

declare(strict_types=1);

/**
 * Вход в визуальный редактор — копия сайта с кликабельными полями.
 * Доступ только администратору.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/site-admin.php';

site_admin_require_login();

$target = '/?ve=1';
header('Location: ' . $target, true, 302);
exit;
