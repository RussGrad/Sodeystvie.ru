<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

site_admin_require_login();

$flash = site_admin_flash_get();

site_admin_render_head('Обзор');
site_admin_render_nav('');
?>
<main class="admin-main">
    <div class="admin-main__inner">
        <h1 class="admin-page-title">Редактирование сайта</h1>
        <p class="admin-page-lead">Изменения сохраняются в файлы на сервере и сразу отображаются на <a href="/" target="_blank" rel="noopener noreferrer">an-sodeystvie.ru</a>. Каталог объектов редактируется в <a href="https://an-realty-crm.ru/login.html" target="_blank" rel="noopener noreferrer">CRM</a> (стадия «Активный»).</p>

        <?php if ($flash !== null) { ?>
            <p class="admin-flash admin-flash--ok" role="status"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>

        <div class="admin-cards">
            <?php foreach (site_admin_editable_datasets() as $section) { ?>
                <a class="admin-card" href="/admin/edit.php?section=<?php echo htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?>">
                    <h2 class="admin-card__title"><?php echo htmlspecialchars(site_admin_dataset_label($section), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="admin-card__text"><?php echo htmlspecialchars(site_admin_card_hint($section), ENT_QUOTES, 'UTF-8'); ?></p>
                </a>
            <?php } ?>
        </div>
    </div>
</main>
<?php
site_admin_render_foot();

function site_admin_card_hint(string $section): string
{
    return match ($section) {
        'settings' => 'Телефон, email, адрес, тексты hero, реквизиты, мессенджеры и иконки',
        'team' => 'Сотрудники на главной и в разделе «О компании»',
        'reviews' => 'Карточки отзывов и сводный рейтинг',
        'cases' => 'Блок «Решённые задачи» на главной',
        'services' => 'Услуги на главной и странице /services/',
        'vacancies' => 'Вакансии на /vacancies/',
        default => '',
    };
}
