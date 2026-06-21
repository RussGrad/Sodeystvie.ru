<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../includes/site-team.php';

site_admin_require_login();

$section = isset($_GET['section']) ? trim((string) $_GET['section']) : '';
if (!in_array($section, site_admin_editable_datasets(), true)) {
    header('Location: /admin/', true, 302);
    exit;
}

$flash = site_admin_flash_get();
$data = site_admin_read_dataset($section);
$csrf = site_admin_csrf_token();
$jsVersion = (string) (@filemtime(__DIR__ . '/../js/admin.js') ?: time());

site_admin_render_head(site_admin_dataset_label($section));
site_admin_render_nav($section);
?>
<main class="admin-main">
    <div class="admin-main__inner">
        <header class="admin-edit-head">
            <h1 class="admin-page-title"><?php echo htmlspecialchars(site_admin_dataset_label($section), ENT_QUOTES, 'UTF-8'); ?></h1>
            <a class="admin-btn admin-btn--ghost" href="/admin/">← К обзору</a>
        </header>

        <?php if ($flash !== null) { ?>
            <p class="admin-flash admin-flash--ok" role="status"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>

        <form class="admin-form" method="post" action="/admin/save.php" enctype="multipart/form-data">
            <input type="hidden" name="section" value="<?php echo htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

            <?php if ($section === 'settings') {
                site_admin_render_settings_form(is_array($data) ? $data : []);
            } else {
                site_admin_render_list_form($section, is_array($data) ? $data : []);
            } ?>

            <div class="admin-form__actions">
                <button class="admin-btn admin-btn--primary" type="submit">Сохранить</button>
            </div>
        </form>
    </div>
</main>
<script src="/js/admin.js?v=<?php echo htmlspecialchars($jsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php
site_admin_render_foot();

/**
 * @param array<string, string> $data
 */
function site_admin_render_settings_form(array $data): void
{
    $fields = [
        ['phone_tel', 'Телефон (href tel)', 'tel'],
        ['phone_display', 'Телефон (как показываем)', 'text'],
        ['email', 'Email', 'email'],
        ['address', 'Адрес офиса', 'text'],
        ['work_hours', 'Режим работы', 'text'],
        ['legal_inn', 'ИНН', 'text'],
        ['legal_ogrn', 'ОГРН', 'text'],
        ['hero_headline', 'Заголовок на главной (H1)', 'text'],
        ['slogan_hero', 'Подзаголовок hero', 'textarea'],
        ['slogan_short', 'Короткий слоган (подвал)', 'text'],
        ['reviews_rating', 'Рейтинг отзывов (например 4.9)', 'text'],
        ['reviews_count', 'Количество отзывов (например 250)', 'text'],
        ['telegram_url', 'Ссылка Telegram', 'url'],
        ['vk_url', 'Ссылка ВКонтакте', 'url'],
        ['max_url', 'Ссылка MAX', 'url'],
    ];
    ?>
    <div class="admin-settings-grid">
        <?php foreach ($fields as [$key, $label, $type]) {
            $value = $data[$key] ?? '';
            ?>
            <label class="admin-field<?php echo $type === 'textarea' ? ' admin-field--wide' : ''; ?>">
                <span class="admin-field__label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if ($type === 'textarea') { ?>
                    <textarea class="admin-field__textarea" name="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" rows="3"><?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <?php } else { ?>
                    <input class="admin-field__input" type="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>
            </label>
        <?php } ?>
    </div>
    <?php
}

/**
 * @param list<array<string, mixed>> $rows
 */
function site_admin_render_list_form(string $section, array $rows): void
{
    ?>
    <div class="admin-items" data-admin-items data-section="<?php echo htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?>">
        <?php
        if (count($rows) === 0) {
            $rows = [[]];
        }
        foreach ($rows as $i => $row) {
            site_admin_render_item_row($section, $i, is_array($row) ? $row : []);
        }
        ?>
    </div>
    <button class="admin-btn admin-btn--ghost admin-add-row" type="button" data-admin-add-row>+ Добавить запись</button>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function site_admin_render_item_row(string $section, int $index, array $row): void
{
    $p = 'items[' . $index . ']';
    ?>
    <fieldset class="admin-item" data-admin-item>
        <div class="admin-item__head">
            <legend class="admin-item__legend">Запись <?php echo $index + 1; ?></legend>
            <button class="admin-item__remove" type="button" data-admin-remove-row aria-label="Удалить запись">×</button>
        </div>
        <div class="admin-item__grid">
            <?php
            switch ($section) {
                case 'team':
                    site_admin_field($p, 'id', 'ID (латиница)', (string) ($row['id'] ?? ''), 'text', 'member-1');
                    site_admin_field($p, 'name', 'Имя / должность на карточке', (string) ($row['name'] ?? ''));
                    site_admin_field($p, 'role', 'Роль', (string) ($row['role'] ?? ''));
                    site_admin_field($p, 'experience', 'Стаж / опыт', (string) ($row['experience'] ?? ''));
                    site_admin_field($p, 'telegram', 'Telegram URL', (string) ($row['telegram'] ?? ''), 'url');
                    site_admin_field($p, 'whatsapp', 'WhatsApp URL', (string) ($row['whatsapp'] ?? ''), 'url');
                    $memberId = (string) ($row['id'] ?? '');
                    ?>
                    <label class="admin-field admin-field--wide">
                        <span class="admin-field__label">Фото (JPG/PNG/WebP, до 5 МБ)</span>
                        <input class="admin-field__input" type="file" name="photo_file[<?php echo htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8'); ?>]" accept="image/jpeg,image/png,image/webp">
                        <?php if ($memberId !== '') {
                            $photoPath = site_team_photo_path($memberId);
                            if ($photoPath !== '') { ?>
                                <img class="admin-item__photo" src="<?php echo htmlspecialchars($photoPath, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="80" height="80">
                            <?php }
                        } ?>
                    </label>
                    <?php
                    break;
                case 'reviews':
                    site_admin_field($p, 'id', 'ID', (string) ($row['id'] ?? ''));
                    site_admin_field($p, 'author', 'Автор', (string) ($row['author'] ?? ''));
                    site_admin_field($p, 'date', 'Дата (YYYY-MM-DD)', (string) ($row['date'] ?? ''));
                    site_admin_field($p, 'rating', 'Оценка 1–5', (string) ($row['rating'] ?? '5'), 'number');
                    site_admin_field($p, 'source', 'Источник (yandex, 2gis)', (string) ($row['source'] ?? 'yandex'));
                    site_admin_field($p, 'text', 'Текст отзыва', (string) ($row['text'] ?? ''), 'textarea');
                    break;
                case 'cases':
                    site_admin_field($p, 'id', 'ID', (string) ($row['id'] ?? ''));
                    site_admin_field($p, 'tag', 'Метка (Покупка, Продажа…)', (string) ($row['tag'] ?? ''));
                    site_admin_field($p, 'title', 'Заголовок', (string) ($row['title'] ?? ''));
                    site_admin_field($p, 'result', 'Результат (коротко)', (string) ($row['result'] ?? ''));
                    site_admin_field($p, 'text', 'Описание', (string) ($row['text'] ?? ''), 'textarea');
                    break;
                case 'services':
                    site_admin_field($p, 'id', 'ID', (string) ($row['id'] ?? ''));
                    site_admin_field($p, 'title', 'Название', (string) ($row['title'] ?? ''));
                    site_admin_field($p, 'short', 'Короткое описание', (string) ($row['short'] ?? ''));
                    site_admin_field($p, 'text', 'Полный текст', (string) ($row['text'] ?? ''), 'textarea');
                    site_admin_field($p, 'icon', 'Иконка (valuation, realtor…)', (string) ($row['icon'] ?? 'realtor'));
                    site_admin_field($p, 'href', 'Ссылка (необяз.)', (string) ($row['href'] ?? ''), 'text');
                    site_admin_field($p, 'hrefLabel', 'Подпись ссылки', (string) ($row['hrefLabel'] ?? ''));
                    site_admin_lines_field($p, 'bullets', 'Пункты списка (по одному в строке)', is_array($row['bullets'] ?? null) ? $row['bullets'] : []);
                    break;
                case 'vacancies':
                    site_admin_field($p, 'id', 'ID', (string) ($row['id'] ?? ''));
                    site_admin_field($p, 'title', 'Название', (string) ($row['title'] ?? ''));
                    site_admin_field($p, 'schedule', 'Занятость', (string) ($row['schedule'] ?? ''));
                    site_admin_field($p, 'salary', 'Зарплата', (string) ($row['salary'] ?? ''));
                    site_admin_field($p, 'location', 'Локация', (string) ($row['location'] ?? ''));
                    site_admin_field($p, 'lead', 'Кратко о вакансии', (string) ($row['lead'] ?? ''), 'textarea');
                    site_admin_lines_field($p, 'duties', 'Обязанности (по строке)', is_array($row['duties'] ?? null) ? $row['duties'] : []);
                    site_admin_lines_field($p, 'requirements', 'Требования', is_array($row['requirements'] ?? null) ? $row['requirements'] : []);
                    site_admin_lines_field($p, 'conditions', 'Условия', is_array($row['conditions'] ?? null) ? $row['conditions'] : []);
                    ?>
                    <label class="admin-field admin-field--check">
                        <input type="hidden" name="<?php echo htmlspecialchars($p . '[active]', ENT_QUOTES, 'UTF-8'); ?>" value="0">
                        <input type="checkbox" name="<?php echo htmlspecialchars($p . '[active]', ENT_QUOTES, 'UTF-8'); ?>" value="1"<?php echo !empty($row['active']) ? ' checked' : ''; ?>>
                        <span>Показывать на сайте</span>
                    </label>
                    <?php
                    break;
            }
            ?>
        </div>
    </fieldset>
    <?php
}

function site_admin_field(string $prefix, string $name, string $label, string $value, string $type = 'text', string $placeholder = ''): void
{
    $fieldName = $prefix . '[' . $name . ']';
    $wide = $type === 'textarea';
    ?>
    <label class="admin-field<?php echo $wide ? ' admin-field--wide' : ''; ?>">
        <span class="admin-field__label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if ($type === 'textarea') { ?>
            <textarea class="admin-field__textarea" name="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>" rows="4"><?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></textarea>
        <?php } else { ?>
            <input class="admin-field__input" type="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $placeholder !== '' ? ' placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
        <?php } ?>
    </label>
    <?php
}

/**
 * @param list<string> $lines
 */
function site_admin_lines_field(string $prefix, string $name, string $label, array $lines): void
{
    $text = implode("\n", array_map(static fn ($l) => (string) $l, $lines));
    ?>
    <label class="admin-field admin-field--wide">
        <span class="admin-field__label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
        <textarea class="admin-field__textarea" name="<?php echo htmlspecialchars($prefix . '[' . $name . ']', ENT_QUOTES, 'UTF-8'); ?>" rows="4"><?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label>
    <?php
}
