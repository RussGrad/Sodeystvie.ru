/**
 * Футер: раскрытие/закрытие вложенных пунктов меню.
 */
(function () {
    'use strict';

    var groups = document.querySelectorAll('[data-footer-menu-group]');
    if (!groups.length) {
        return;
    }

    function setOpen(group, open) {
        var btn = group.querySelector('.site-footer__submenu-toggle');
        group.classList.toggle('site-footer__menu-item--open', open);
        if (btn) {
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    function closeOthers(current) {
        for (var i = 0; i < groups.length; i++) {
            if (groups[i] !== current) {
                setOpen(groups[i], false);
            }
        }
    }

    for (var i = 0; i < groups.length; i++) {
        (function (group) {
            var btn = group.querySelector('.site-footer__submenu-toggle');
            if (!btn) {
                return;
            }

            btn.addEventListener('click', function () {
                var willOpen = !group.classList.contains('site-footer__menu-item--open');
                closeOthers(group);
                setOpen(group, willOpen);
            });
        })(groups[i]);
    }
})();
