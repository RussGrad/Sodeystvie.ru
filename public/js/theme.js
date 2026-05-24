/**
 * Переключение светлой/тёмной темы: data-theme на <html> и запись в localStorage.
 */
(function () {
    'use strict';

    var btn = document.getElementById('site-theme-toggle');
    if (!btn) {
        return;
    }

    var moon = btn.querySelector('.site-header__theme-icon--moon');
    var sun = btn.querySelector('.site-header__theme-icon--sun');

    function isDarkTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function syncThemeIcons() {
        var dark = isDarkTheme();
        if (moon) {
            moon.hidden = !dark;
        }
        if (sun) {
            sun.hidden = dark;
        }
    }

    function syncAria() {
        btn.setAttribute(
            'aria-label',
            isDarkTheme() ? 'Включить светлую тему' : 'Включить тёмную тему',
        );
    }

    function syncAll() {
        syncThemeIcons();
        syncAria();
    }

    syncAll();

    btn.addEventListener('click', function () {
        var root = document.documentElement;
        var next = isDarkTheme() ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        syncAll();
        try {
            localStorage.setItem('sodeystvie-theme', next);
        } catch (e) {
            /* режим приватного окна — игнорируем */
        }
    });
})();
