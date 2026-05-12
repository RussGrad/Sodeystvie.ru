/**
 * Переключение светлой/тёмной темы: data-theme на <html> и запись в localStorage.
 */
(function () {
    'use strict';

    var btn = document.getElementById('site-theme-toggle');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', function () {
        var root = document.documentElement;
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try {
            localStorage.setItem('sodeystvie-theme', next);
        } catch (e) {
            /* режим приватного окна — игнорируем */
        }
    });
})();
