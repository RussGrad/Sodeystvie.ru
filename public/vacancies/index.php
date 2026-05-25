<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

$pageTitle = site_format_page_title('Вакансии');
$currentNav = 'vacancies';

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner" id="main">
    <div class="container">
        <h1 class="page-main__heading">Вакансии</h1>
        <p class="page-main__lead">Раздел в разработке: здесь будут открытые вакансии и форма отклика.</p>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';

