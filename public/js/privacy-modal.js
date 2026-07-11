/**
 * Модальное окно политики конфиденциальности.
 */
(function () {
    'use strict';

    var modal = document.getElementById('privacy-modal');
    if (!modal) {
        return;
    }

    var panel = modal.querySelector('.modal-privacy__panel');
    var body = modal.querySelector('.modal-privacy__body');
    var closeTimer = null;
    var lastFocus = null;

    function isOpen() {
        return modal.classList.contains('modal-privacy--open');
    }

    function open() {
        if (isOpen()) {
            return;
        }

        lastFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        modal.removeAttribute('inert');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('has-modal');

        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                modal.classList.add('modal-privacy--open');
            });
        });

        if (body) {
            body.scrollTop = 0;
        }

        window.setTimeout(function () {
            var closeBtn = modal.querySelector('.modal-privacy__close');
            if (closeBtn instanceof HTMLElement) {
                closeBtn.focus();
            } else if (panel instanceof HTMLElement) {
                panel.focus();
            }
        }, 60);
    }

    function finishClose() {
        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('inert', '');
        document.body.classList.remove('has-modal');
        if (lastFocus instanceof HTMLElement) {
            lastFocus.focus();
        }
        closeTimer = null;
    }

    function close() {
        if (!isOpen()) {
            return;
        }

        modal.classList.remove('modal-privacy--open');

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

    document.addEventListener('click', function (e) {
        var target = e.target;
        if (!target || !target.closest) {
            return;
        }

        var opener = target.closest('[data-privacy-open]');
        if (opener) {
            e.preventDefault();
            open();
        }
    });

    modal.addEventListener('click', function (e) {
        var target = e.target;
        if (target && target.closest && target.closest('[data-privacy-modal-close]')) {
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
        true,
    );

    var path = window.location.pathname.replace(/\/+$/, '') || '/';
    if (path === '/privacy') {
        open();
    }
})();
