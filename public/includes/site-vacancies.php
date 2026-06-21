<?php

declare(strict_types=1);

/**
 * Вакансии: public/data/vacancies.json
 */

function site_vacancies_data_path(): string
{
    return dirname(__DIR__) . '/data/vacancies.json';
}

/**
 * @return list<array{
 *   id: string,
 *   title: string,
 *   schedule: string,
 *   salary: string,
 *   location: string,
 *   lead: string,
 *   duties: list<string>,
 *   requirements: list<string>,
 *   conditions: list<string>,
 *   active: bool
 * }>
 */
function site_vacancies_all(bool $activeOnly = true): array
{
    static $cache = null;
    if ($cache !== null) {
        return $activeOnly ? array_values(array_filter($cache, static fn (array $v): bool => !empty($v['active']))) : $cache;
    }

    $path = site_vacancies_data_path();
    if (!is_readable($path)) {
        $cache = [];

        return $cache;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        $cache = [];

        return $cache;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $cache = [];

        return $cache;
    }

    $out = [];
    foreach ($decoded as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = isset($row['id']) ? trim((string) $row['id']) : '';
        $title = isset($row['title']) ? trim((string) $row['title']) : '';
        if ($id === '' || $title === '') {
            continue;
        }

        $out[] = [
            'id' => $id,
            'title' => $title,
            'schedule' => isset($row['schedule']) ? trim((string) $row['schedule']) : '',
            'salary' => isset($row['salary']) ? trim((string) $row['salary']) : '',
            'location' => isset($row['location']) ? trim((string) $row['location']) : '',
            'lead' => isset($row['lead']) ? trim((string) $row['lead']) : '',
            'duties' => site_vacancies_string_list($row['duties'] ?? []),
            'requirements' => site_vacancies_string_list($row['requirements'] ?? []),
            'conditions' => site_vacancies_string_list($row['conditions'] ?? []),
            'active' => !isset($row['active']) || !empty($row['active']),
        ];
    }

    $cache = $out;

    return $activeOnly ? array_values(array_filter($cache, static fn (array $v): bool => !empty($v['active']))) : $cache;
}

/**
 * @return list<string>
 */
function site_vacancies_string_list(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }
    $out = [];
    foreach ($value as $item) {
        $s = trim((string) $item);
        if ($s !== '') {
            $out[] = $s;
        }
    }

    return $out;
}

/**
 * @return list<array{title: string, text: string}>
 */
function site_vacancies_perks(): array
{
    return [
        [
            'title' => 'Обучение с наставником',
            'text' => 'Помогаем войти в профессию: скрипты, CRM, юридические основы сделок.',
        ],
        [
            'title' => 'База объектов и CRM',
            'text' => 'Работаете с актуальными предложениями и единой системой учёта.',
        ],
        [
            'title' => 'Прозрачная мотивация',
            'text' => 'Понятные условия: оклад, процент с сделок, бонусы за результат.',
        ],
        [
            'title' => 'Команда и офис',
            'text' => 'Офис в Иркутске, юридическая поддержка и слаженная работа агентов.',
        ],
    ];
}

/**
 * @return list<array{step: string, title: string, text: string}>
 */
function site_vacancies_hiring_steps(): array
{
    return [
        [
            'step' => '1',
            'title' => 'Отклик',
            'text' => 'Оставьте заявку на сайте или позвоните — уточним вакансию и ваш опыт.',
        ],
        [
            'step' => '2',
            'title' => 'Знакомство',
            'text' => 'Короткое интервью в офисе или онлайн: обсудим задачи и формат работы.',
        ],
        [
            'step' => '3',
            'title' => 'Старт',
            'text' => 'Оформление, onboarding и первые задачи с куратором.',
        ],
    ];
}

/**
 * @param array{
 *   id: string,
 *   title: string,
 *   schedule: string,
 *   salary: string,
 *   location: string,
 *   lead: string,
 *   duties: list<string>,
 *   requirements: list<string>,
 *   conditions: list<string>,
 *   active: bool
 * } $vacancy
 */
function site_render_vacancy_card(array $vacancy): void
{
    ?>
    <article class="vacancy-card" id="vacancy-<?php echo htmlspecialchars($vacancy['id'], ENT_QUOTES, 'UTF-8'); ?>">
        <header class="vacancy-card__head">
            <div class="vacancy-card__titles">
                <h2 class="vacancy-card__title"><?php echo htmlspecialchars($vacancy['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <?php if ($vacancy['lead'] !== '') { ?>
                    <p class="vacancy-card__lead"><?php echo htmlspecialchars($vacancy['lead'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php } ?>
            </div>
            <ul class="vacancy-card__meta">
                <?php if ($vacancy['salary'] !== '') { ?>
                    <li class="vacancy-card__meta-item vacancy-card__meta-item--salary">
                        <?php echo htmlspecialchars($vacancy['salary'], ENT_QUOTES, 'UTF-8'); ?>
                    </li>
                <?php } ?>
                <?php if ($vacancy['schedule'] !== '') { ?>
                    <li class="vacancy-card__meta-item"><?php echo htmlspecialchars($vacancy['schedule'], ENT_QUOTES, 'UTF-8'); ?></li>
                <?php } ?>
                <?php if ($vacancy['location'] !== '') { ?>
                    <li class="vacancy-card__meta-item"><?php echo htmlspecialchars($vacancy['location'], ENT_QUOTES, 'UTF-8'); ?></li>
                <?php } ?>
            </ul>
        </header>

        <div class="vacancy-card__body">
            <?php site_render_vacancy_list_block('Обязанности', $vacancy['duties']); ?>
            <?php site_render_vacancy_list_block('Требования', $vacancy['requirements']); ?>
            <?php site_render_vacancy_list_block('Условия', $vacancy['conditions']); ?>
        </div>

        <footer class="vacancy-card__actions">
            <button
                type="button"
                class="vacancy-card__apply"
                data-lead-open
                data-lead-topic="vacancy-<?php echo htmlspecialchars($vacancy['id'], ENT_QUOTES, 'UTF-8'); ?>"
                aria-haspopup="dialog"
                aria-controls="lead-modal"
            >
                Откликнуться
            </button>
            <a class="vacancy-card__mailto" href="<?php echo htmlspecialchars(site_vacancy_mailto_href($vacancy), ENT_QUOTES, 'UTF-8'); ?>">
                Написать на email
            </a>
        </footer>
    </article>
    <?php
}

/**
 * @param list<string> $items
 */
function site_render_vacancy_list_block(string $heading, array $items): void
{
    if (count($items) === 0) {
        return;
    }
    ?>
    <section class="vacancy-card__block">
        <h3 class="vacancy-card__block-title"><?php echo htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?></h3>
        <ul class="vacancy-card__list">
            <?php foreach ($items as $item) { ?>
                <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php } ?>
        </ul>
    </section>
    <?php
}

/**
 * @param array{id: string, title: string} $vacancy
 */
function site_vacancy_mailto_href(array $vacancy): string
{
    $subject = 'Отклик на вакансию: ' . $vacancy['title'];
    $body = "Здравствуйте!\n\nХочу откликнуться на вакансию «" . $vacancy['title'] . "».\n\nИмя:\nТелефон:\n";

    return 'mailto:' . rawurlencode(site_email_address())
        . '?subject=' . rawurlencode($subject)
        . '&body=' . rawurlencode($body);
}
