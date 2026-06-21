<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

site_admin_session_start();
if (site_admin_is_logged_in()) {
    site_admin_logout();
}
header('Location: /admin/login.php', true, 302);
exit;
