/**
 * Подвал: кнопка «Наверх».
 */
(function () {
    'use strict';

    var topBtn = document.getElementById('site-footer-top');
    if (!topBtn) {
        return;
    }

    topBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
