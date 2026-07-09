<?php

declare(strict_types=1);

/**
 * Скопируйте в crm-config.local.php (файл в git не попадает).
 * Альтернатива .env на REG.RU, если dot-файлы неудобны в панели.
 */
return [
    'PUBLIC_SITE_API_KEY' => 'сгенерируйте-openssl-rand-hex-32',
    'YANDEX_MAPS_API_KEY' => 'ваш_ключ_яндекс_карт',
];
