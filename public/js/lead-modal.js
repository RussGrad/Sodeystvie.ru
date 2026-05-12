/**
 * Модальное окно заявки: открытие/закрытие с анимацией, Escape, отправка формы.
 */
(function () {
    'use strict';

    var modal = document.getElementById('lead-modal');
    var openBtn = document.getElementById('site-header-lead-open');
    var form = document.getElementById('lead-form');
    var successMsg = document.getElementById('lead-form-success');

    if (!modal || !openBtn) {
        return;
    }

    var panel = modal.querySelector('.modal-lead__panel');
    var firstInput = document.getElementById('lead-name');
    var closeTimer = null;

    function isOpen() {
        return modal.classList.contains('modal-lead--open');
    }

    function open() {
        var header = document.getElementById('site-header');
        var burger = document.getElementById('site-header-burger');
        if (header) {
            header.classList.remove('site-header--nav-open');
        }
        document.body.classList.remove('site-header--nav-open');
        if (burger) {
            burger.setAttribute('aria-expanded', 'false');
            burger.setAttribute('aria-label', 'Открыть меню');
        }

        modal.removeAttribute('inert');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('has-modal');

        if (form) {
            form.removeAttribute('hidden');
            form.reset();
        }
        if (successMsg) {
            successMsg.setAttribute('hidden', '');
        }

        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                modal.classList.add('modal-lead--open');
            });
        });

        window.setTimeout(function () {
            if (firstInput) {
                firstInput.focus();
            } else if (panel) {
                panel.focus();
            }
        }, 60);
    }

    function finishClose() {
        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('inert', '');
        document.body.classList.remove('has-modal');
        openBtn.focus();
        closeTimer = null;
    }

    function close() {
        if (!modal.classList.contains('modal-lead--open')) {
            return;
        }
        modal.classList.remove('modal-lead--open');

        if (closeTimer) {
            window.clearTimeout(closeTimer);
        }

        var done = false;
        function end() {
            if (done) {
                return;
            }
            done = true;
            modal.removeEventListener('transitionend', onEnd);
            finishClose();
        }

        function onEnd(e) {
            if (e.target === panel && (e.propertyName === 'transform' || e.propertyName === 'opacity')) {
                end();
            }
        }

        modal.addEventListener('transitionend', onEnd);
        closeTimer = window.setTimeout(end, 400);
    }

    openBtn.addEventListener('click', function () {
        open();
    });

    // Любая кнопка/ссылка с data-lead-open открывает модалку
    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t || !t.closest) {
            return;
        }
        var opener = t.closest('[data-lead-open]');
        if (!opener) {
            return;
        }
        e.preventDefault();
        open();
    });

    modal.addEventListener('click', function (e) {
        var t = e.target;
        if (t && t.closest && t.closest('[data-lead-modal-close]')) {
            close();
        }
    });

    document.addEventListener(
        'keydown',
        function (e) {
            if (e.key !== 'Escape' || !isOpen()) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            close();
        },
        true
    );

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            form.setAttribute('hidden', '');
            if (successMsg) {
                successMsg.removeAttribute('hidden');
                successMsg.focus();
            }
        });
    }
})();
