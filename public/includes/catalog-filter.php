<?php

declare(strict_types=1);

/**
 * Боковой фильтр каталога. Параметры совместимы с формой hero (type, rooms, price, region).
 *
 * @param array<string, string> $filters
 */
function site_render_catalog_filter(array $filters, string $action = '/catalog/'): void
{
    $type = $filters['objectType'] ?? '';
    $rooms = $filters['rooms'] ?? '';
    $price = $filters['price'] ?? '';
    $region = $filters['region'] ?? '';
    $city = $filters['city'] ?? '';
    $q = $filters['q'] ?? '';
    $areaMin = $filters['area_min'] ?? '';
    $areaMax = $filters['area_max'] ?? '';
    $priceMin = $filters['price_min'] ?? '';
    $priceMax = $filters['price_max'] ?? '';
    ?>
    <form class="catalog-filter" method="get" action="<?php echo htmlspecialchars($action, ENT_QUOTES, 'UTF-8'); ?>">
        <h2 class="catalog-filter__title">Фильтр</h2>

        <label class="catalog-filter__field">
            <span class="catalog-filter__label">Поиск</span>
            <input
                type="search"
                class="catalog-filter__input"
                name="q"
                value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Район, ЖК, адрес…"
                autocomplete="off"
            >
        </label>

        <label class="catalog-filter__field">
            <span class="catalog-filter__label">Город</span>
            <select class="catalog-filter__select" name="region">
                <option value=""<?php echo $region === '' && $city === '' ? ' selected' : ''; ?>>Любой</option>
                <option value="irkutsk"<?php echo $region === 'irkutsk' ? ' selected' : ''; ?>>Иркутск</option>
                <option value="angarsk"<?php echo $region === 'angarsk' ? ' selected' : ''; ?>>Ангарск</option>
                <option value="bratsk"<?php echo $region === 'bratsk' ? ' selected' : ''; ?>>Братск</option>
                <option value="shelekhov"<?php echo $region === 'shelekhov' ? ' selected' : ''; ?>>Шелехов</option>
            </select>
        </label>

        <label class="catalog-filter__field">
            <span class="catalog-filter__label">Тип объекта</span>
            <select class="catalog-filter__select" name="type">
                <option value=""<?php echo $type === '' ? ' selected' : ''; ?>>Любой</option>
                <option value="flat"<?php echo $type === 'flat' ? ' selected' : ''; ?>>Квартира</option>
                <option value="house"<?php echo $type === 'house' ? ' selected' : ''; ?>>Дом</option>
                <option value="land"<?php echo $type === 'land' || $type === 'plot' ? ' selected' : ''; ?>>Участок</option>
                <option value="commercial"<?php echo $type === 'commercial' ? ' selected' : ''; ?>>Коммерция</option>
            </select>
        </label>

        <label class="catalog-filter__field">
            <span class="catalog-filter__label">Комнат</span>
            <select class="catalog-filter__select" name="rooms">
                <option value=""<?php echo $rooms === '' ? ' selected' : ''; ?>>Любое</option>
                <option value="studio"<?php echo $rooms === 'studio' ? ' selected' : ''; ?>>Студия</option>
                <option value="1"<?php echo $rooms === '1' ? ' selected' : ''; ?>>1</option>
                <option value="2"<?php echo $rooms === '2' ? ' selected' : ''; ?>>2</option>
                <option value="3"<?php echo $rooms === '3' ? ' selected' : ''; ?>>3</option>
                <option value="4plus"<?php echo $rooms === '4plus' ? ' selected' : ''; ?>>4+</option>
            </select>
        </label>

        <div class="catalog-filter__row">
            <label class="catalog-filter__field catalog-filter__field--half">
                <span class="catalog-filter__label">Площадь от, м²</span>
                <input
                    type="number"
                    class="catalog-filter__input"
                    name="area_min"
                    min="0"
                    step="1"
                    value="<?php echo htmlspecialchars($areaMin, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="—"
                >
            </label>
            <label class="catalog-filter__field catalog-filter__field--half">
                <span class="catalog-filter__label">до</span>
                <input
                    type="number"
                    class="catalog-filter__input"
                    name="area_max"
                    min="0"
                    step="1"
                    value="<?php echo htmlspecialchars($areaMax, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="—"
                >
            </label>
        </div>

        <label class="catalog-filter__field">
            <span class="catalog-filter__label">Цена (диапазон)</span>
            <select class="catalog-filter__select" name="price">
                <option value=""<?php echo $price === '' ? ' selected' : ''; ?>>Любая</option>
                <option value="0-3"<?php echo $price === '0-3' ? ' selected' : ''; ?>>до 3 млн ₽</option>
                <option value="3-5"<?php echo $price === '3-5' ? ' selected' : ''; ?>>3–5 млн ₽</option>
                <option value="5-10"<?php echo $price === '5-10' ? ' selected' : ''; ?>>5–10 млн ₽</option>
                <option value="10-20"<?php echo $price === '10-20' ? ' selected' : ''; ?>>10–20 млн ₽</option>
                <option value="20+"<?php echo $price === '20+' ? ' selected' : ''; ?>>от 20 млн ₽</option>
            </select>
        </label>

        <div class="catalog-filter__row">
            <label class="catalog-filter__field catalog-filter__field--half">
                <span class="catalog-filter__label">Цена от, ₽</span>
                <input
                    type="number"
                    class="catalog-filter__input"
                    name="price_min"
                    min="0"
                    step="100000"
                    value="<?php echo htmlspecialchars($priceMin, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="—"
                >
            </label>
            <label class="catalog-filter__field catalog-filter__field--half">
                <span class="catalog-filter__label">до</span>
                <input
                    type="number"
                    class="catalog-filter__input"
                    name="price_max"
                    min="0"
                    step="100000"
                    value="<?php echo htmlspecialchars($priceMax, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="—"
                >
            </label>
        </div>

        <div class="catalog-filter__actions">
            <button type="submit" class="catalog-filter__submit">Показать</button>
            <a class="catalog-filter__reset" href="/catalog/">Сбросить</a>
        </div>
    </form>
    <?php
}
