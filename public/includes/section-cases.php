<?php

declare(strict_types=1);

require_once __DIR__ . '/site-cases.php';

$cases = site_cases_all();
if (count($cases) === 0) {
    return;
}

?>
<section class="cases" aria-labelledby="cases-title">
    <div class="container">
        <header class="cases__header">
            <h2 class="cases__title" id="cases-title">Решённые задачи</h2>
            <p class="cases__lead">Реальные ситуации клиентов в Иркутске — от ипотеки и новостроек до сложных продаж.</p>
        </header>

        <div class="cases__grid">
            <?php foreach ($cases as $case) {
                site_render_case_card($case);
            } ?>
        </div>
    </div>
</section>
