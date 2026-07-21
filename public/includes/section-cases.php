<?php

declare(strict_types=1);

require_once __DIR__ . '/site-cases.php';
require_once __DIR__ . '/visual-editor.php';

$cases = site_cases_all();
if (count($cases) === 0) {
    return;
}

$casesTitle = site_content_setting('cases_section_title', 'Решённые задачи');
$casesLead = site_content_setting(
    'cases_section_lead',
    'Реальные ситуации клиентов в Иркутске — от ипотеки и новостроек до сложных продаж.'
);

?>
<section class="cases" aria-labelledby="cases-title">
    <div class="container">
        <header class="cases__header">
            <h2 class="cases__title" id="cases-title"<?php echo site_ve_attrs('cases_section_title', 'text', 'Заголовок блока кейсов'); ?>><?php echo htmlspecialchars($casesTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="cases__lead"<?php echo site_ve_attrs('cases_section_lead', 'textarea', 'Описание блока кейсов'); ?>><?php echo htmlspecialchars($casesLead, ENT_QUOTES, 'UTF-8'); ?></p>
        </header>

        <div class="cases__grid">
            <?php foreach ($cases as $case) {
                site_render_case_card($case);
            } ?>
        </div>
    </div>
</section>
