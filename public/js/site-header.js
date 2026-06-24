/**
 * Шапка: бургер, выпадающее меню «О компании», закрытие по клику вне / Escape / смена ширины.
 */
(function () {
    'use strict';

    var header = document.getElementById('site-header');
    var burger = document.getElementById('site-header-burger');
    var nav = document.getElementById('site-header-menu');
    if (!header || !burger || !nav) {
        return;
    }

    var mq = window.matchMedia('(max-width: 1023px)');
    var dropdownItems = header.querySelectorAll('[data-nav-dropdown]');

    function setNavOpen(open) {
        header.classList.toggle('site-header--nav-open', open);
        document.body.classList.toggle('site-header--nav-open', open);
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        burger.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
    }

    function closeAllDropdowns() {
        for (var i = 0; i < dropdownItems.length; i++) {
            var li = dropdownItems[i];
            li.classList.remove('site-header__menu-item--open');
            var btn = li.querySelector('.site-header__dropdown-trigger');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
            }
        }
    }

    function toggleDropdown(li) {
        var btn = li.querySelector('.site-header__dropdown-trigger');
        var willOpen = !li.classList.contains('site-header__menu-item--open');

        for (var i = 0; i < dropdownItems.length; i++) {
            var other = dropdownItems[i];
            if (other !== li) {
                other.classList.remove('site-header__menu-item--open');
                var ob = other.querySelector('.site-header__dropdown-trigger');
                if (ob) {
                    ob.setAttribute('aria-expanded', 'false');
                }
            }
        }

        li.classList.toggle('site-header__menu-item--open', willOpen);
        if (btn) {
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        }
    }

    burger.addEventListener('click', function () {
        setNavOpen(!header.classList.contains('site-header--nav-open'));
    });

    nav.addEventListener('click', function (e) {
        if (mq.matches && e.target === nav) {
            setNavOpen(false);
            closeAllDropdowns();
            return;
        }

        if (e.target.closest && e.target.closest('.site-header__submenu-link')) {
            closeAllDropdowns();
        }

        var trigger = e.target.closest('.site-header__dropdown-trigger');
        if (trigger && header.contains(trigger)) {
            var dropdownLi = trigger.closest('[data-nav-dropdown]');
            if (mq.matches && dropdownLi && !dropdownLi.classList.contains('site-header__menu-item--open')) {
                e.preventDefault();
                toggleDropdown(dropdownLi);
                return;
            }
            if (!mq.matches) {
                closeAllDropdowns();
            }
            return;
        }

        var topLink = e.target.closest('.site-header__menu-link');
        if (topLink && nav.contains(topLink) && !topLink.closest('.site-header__submenu')) {
            closeAllDropdowns();
        }

        if (!mq.matches) {
            return;
        }
        var t = e.target;
        if (t && t.closest && t.closest('a')) {
            setNavOpen(false);
            closeAllDropdowns();
        }
    });

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (t && t.closest && t.closest('#site-header')) {
            return;
        }
        closeAllDropdowns();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') {
            return;
        }
        var anyOpen = false;
        for (var i = 0; i < dropdownItems.length; i++) {
            if (dropdownItems[i].classList.contains('site-header__menu-item--open')) {
                anyOpen = true;
                break;
            }
        }
        if (anyOpen) {
            e.stopPropagation();
            closeAllDropdowns();
            return;
        }
        setNavOpen(false);
    });

    window.addEventListener('resize', function () {
        if (!mq.matches) {
            setNavOpen(false);
            closeAllDropdowns();
        }
    });

    var scrollThreshold = 12;

    function onScroll() {
        header.classList.toggle('site-header--scrolled', window.scrollY > scrollThreshold);
    }

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
})();
