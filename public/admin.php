<?php

declare(strict_types=1);

/**
 * Запасной вход в админку (если /admin/ отдаёт 404 на хостинге).
 * Основной URL: /admin/login.php
 */
header('Location: /admin/login.php', true, 302);
exit;
