/**
 * Переключение светлой/тёмной темы: data-theme на <html> и запись в localStorage.
 */
(function () {
    'use strict';

    var btn = document.getElementById('site-theme-toggle');
    if (!btn) {
        return;
    }

    function syncAria() {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        btn.setAttribute('aria-label', isDark ? 'Включить светлую тему' : 'Включить тёмную тему');
    }

    syncAria();

    btn.addEventListener('click', function () {
        var root = document.documentElement;
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        syncAria();
        try {
            localStorage.setItem('sodeystvie-theme', next);
        } catch (e) {
            /* режим приватного окна — игнорируем */
        }
    });
})();
