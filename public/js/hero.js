/**
 * Первый экран: вкладки типа сделки + динамическая подпись типа объекта.
 */
(function () {
    'use strict';

    var hero = document.querySelector('.hero');
    var dealInput = document.getElementById('hero-deal');
    if (!hero || !dealInput) {
        return;
    }

    var tabs = hero.querySelectorAll('.hero__tab');
    var typeLabel = document.getElementById('hero-lbl-type');
    var typeSelect = hero.querySelector('select[name="type"]');
    var primaryAction = document.getElementById('hero-primary-action');
    var leadOpenBtn = document.getElementById('site-header-lead-open');

    function setTypeLabel() {
        if (!typeLabel || !typeSelect) {
            return;
        }

        var v = String(typeSelect.value || '');
        var map = {
            flat: 'Квартиру',
            house: 'Дом',
            land: 'Участок',
            commercial: 'Коммерцию',
        };

        typeLabel.textContent = map[v] || 'Квартиру';
    }

    function setPrimaryActionForDeal(deal) {
        if (!primaryAction) {
            return;
        }

        var isLead = deal === 'sell' || deal === 'rent_out';

        if (isLead) {
            primaryAction.textContent = 'Оставить заявку';
            primaryAction.setAttribute('type', 'button');
            primaryAction.removeAttribute('form');
            primaryAction.setAttribute('aria-haspopup', 'dialog');
            primaryAction.setAttribute('aria-controls', 'lead-modal');
            primaryAction.setAttribute('data-action', 'lead');
            return;
        }

        primaryAction.textContent = 'Найти';
        primaryAction.setAttribute('type', 'submit');
        primaryAction.setAttribute('form', 'hero-search-form');
        primaryAction.removeAttribute('aria-haspopup');
        primaryAction.removeAttribute('aria-controls');
        primaryAction.setAttribute('data-action', 'search');
    }

    hero.addEventListener('click', function (e) {
        var actionBtn = e.target.closest('#hero-primary-action');
        if (actionBtn && hero.contains(actionBtn)) {
            if (actionBtn.getAttribute('data-action') === 'lead' && leadOpenBtn) {
                leadOpenBtn.click();
            }
        }
    });

    hero.addEventListener('click', function (e) {
        var tab = e.target.closest('.hero__tab');
        if (!tab || !hero.contains(tab)) {
            return;
        }

        var deal = tab.getAttribute('data-deal');
        if (!deal) {
            return;
        }

        dealInput.value = deal;
        setPrimaryActionForDeal(deal);

        for (var i = 0; i < tabs.length; i++) {
            var t = tabs[i];
            var on = t === tab;
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.classList.toggle('hero__tab--active', on);
        }
    });

    if (typeSelect && typeLabel) {
        typeSelect.addEventListener('change', setTypeLabel);
        setTypeLabel();
    }

    setPrimaryActionForDeal(String(dealInput.value || 'buy'));
})();
