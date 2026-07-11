<?php

declare(strict_types=1);

require_once __DIR__ . '/site-content.php';

function site_footer_reprint_notice(): string
{
    return site_content_setting(
        'footer_reprint_notice',
        'Перепечатка материалов сайта без письменного разрешения запрещена.'
    );
}

function site_footer_info_disclaimer(): string
{
    return site_content_setting(
        'footer_info_disclaimer',
        'Вся информация, размещённая на сайте, носит ознакомительный характер и может отличаться от действительности!'
    );
}

function site_privacy_policy_text(): string
{
    return site_content_setting('privacy_policy', site_privacy_policy_default_text());
}

function site_privacy_policy_default_text(): string
{
    $operator = SITE_LEGAL_NAME;
    $email = site_email_address();
    $address = site_postal_address();

    return <<<TEXT
Настоящая Политика конфиденциальности определяет порядок обработки и защиты персональных данных пользователей сайта {$operator} (далее — Оператор).

1. Общие положения
Оператор обрабатывает персональные данные в соответствии с Федеральным законом № 152-ФЗ «О персональных данных» и только в объёме, необходимом для предоставления услуг агентства недвижимости, обратной связи и улучшения работы сайта.

2. Какие данные мы можем получать
При заполнении форм на сайте и обращении к нам вы можете передать: имя, номер телефона, адрес электронной почты, сведения об интересующем объекте недвижимости, а также иные данные, которые вы добровольно указываете в сообщении.

3. Цели обработки
Персональные данные используются для связи с вами, подбора и сопровождения сделок с недвижимостью, консультаций, обработки заявок, направления информации об услугах Оператора, а также для исполнения требований законодательства.

4. Передача третьим лицам
Оператор не передаёт персональные данные третьим лицам, за исключением случаев, предусмотренных законом, либо когда это необходимо для исполнения договора с вами (например, банкам, застройщикам, нотариусам — только при вашем согласии и в рамках сделки).

5. Cookies и аналитика
Сайт может использовать файлы cookies и технические данные браузера для корректной работы страниц, сохранения настроек и анализа посещаемости. Подробнее — на странице «Согласие на использование cookies».

6. Срок хранения и защита
Данные хранятся не дольше, чем это требуется для целей обработки, и защищаются организационными и техническими мерами, доступными Оператору.

7. Ваши права
Вы вправе запросить уточнение, блокирование или удаление персональных данных, отозвать согласие на обработку и обратиться с вопросами по адресу: {$email}.

8. Контакты Оператора
{$operator}
Адрес: {$address}
Email: {$email}

Оператор вправе обновлять настоящую Политику. Актуальная версия всегда размещена на этой странице.
TEXT;
}

/**
 * @return list<string>
 */
function site_privacy_policy_paragraphs(): array
{
    $text = trim(site_privacy_policy_text());
    if ($text === '') {
        return [];
    }

    $parts = preg_split("/\n\s*\n/u", $text) ?: [];
    $paragraphs = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        if ($part !== '') {
            $paragraphs[] = $part;
        }
    }

    return $paragraphs;
}

function site_privacy_policy_is_section_heading(string $paragraph): bool
{
    return (bool) preg_match('/^\d+(?:\.\d+)*\.\s/u', $paragraph);
}

function site_privacy_policy_render(string $wrapperClass = 'legal-text'): void
{
    $paragraphs = site_privacy_policy_paragraphs();
    if (count($paragraphs) === 0) {
        echo '<p class="legal-text__empty">Текст политики конфиденциальности будет опубликован после согласования.</p>';

        return;
    }

    echo '<div class="' . htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8') . '">';
    foreach ($paragraphs as $paragraph) {
        $lines = preg_split("/\r\n|\r|\n/u", $paragraph) ?: [];
        $firstLine = trim((string) ($lines[0] ?? ''));
        $rest = trim(implode("\n", array_slice($lines, 1)));

        if ($firstLine !== '' && site_privacy_policy_is_section_heading($firstLine)) {
            echo '<p class="legal-text__section">' . htmlspecialchars($firstLine, ENT_QUOTES, 'UTF-8') . '</p>';
            if ($rest !== '') {
                echo '<p class="legal-text__paragraph">' . nl2br(htmlspecialchars($rest, ENT_QUOTES, 'UTF-8')) . '</p>';
            }
            continue;
        }

        echo '<p class="legal-text__paragraph">' . nl2br(htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8')) . '</p>';
    }
    echo '</div>';
}
