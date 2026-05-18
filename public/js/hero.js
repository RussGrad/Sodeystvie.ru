/**
 * Первый экран: вкладки типа сделки + слайдер фона.
 */
(function () {
  'use strict';

  var hero = document.querySelector('.hero');
  if (!hero) {
    return;
  }

  var dealInput = document.getElementById('hero-deal');
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

  if (dealInput) {
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
  }

  // --- Фоновый слайдер ---
  var sliderRoot = hero.querySelector('[data-hero-slider]');
  if (!sliderRoot) {
    return;
  }

  var slides = Array.prototype.slice.call(sliderRoot.querySelectorAll('.hero__slide'));
  if (slides.length === 0) {
    return;
  }

  var sliderUi = hero.querySelector('[data-hero-slider-ui]');
  var dots = sliderUi
    ? Array.prototype.slice.call(sliderUi.querySelectorAll('[data-hero-dot]'))
    : [];
  var index = 0;
  var autoplayMs = 7000;
  var autoplayTimer = null;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var touchStartX = 0;

  function goTo(nextIndex) {
    if (slides.length < 2) {
      return;
    }
    index = ((nextIndex % slides.length) + slides.length) % slides.length;
    for (var i = 0; i < slides.length; i++) {
      slides[i].classList.toggle('is-active', i === index);
    }
    for (var d = 0; d < dots.length; d++) {
      var on = d === index;
      dots[d].classList.toggle('hero__slider-dot--active', on);
      dots[d].setAttribute('aria-selected', on ? 'true' : 'false');
    }
  }

  function stopAutoplay() {
    if (autoplayTimer) {
      window.clearInterval(autoplayTimer);
      autoplayTimer = null;
    }
  }

  function startAutoplay() {
    if (slides.length < 2 || reduceMotion) {
      return;
    }
    stopAutoplay();
    autoplayTimer = window.setInterval(function () {
      goTo(index + 1);
    }, autoplayMs);
  }

  hero.addEventListener('click', function (e) {
    if (slides.length < 2) {
      return;
    }
    if (e.target.closest('[data-hero-prev]')) {
      e.preventDefault();
      goTo(index - 1);
      startAutoplay();
      return;
    }
    if (e.target.closest('[data-hero-next]')) {
      e.preventDefault();
      goTo(index + 1);
      startAutoplay();
      return;
    }
    var dot = e.target.closest('[data-hero-dot]');
    if (dot && sliderUi && sliderUi.contains(dot)) {
      e.preventDefault();
      var dotIndex = parseInt(dot.getAttribute('data-hero-dot'), 10);
      if (!isNaN(dotIndex)) {
        goTo(dotIndex);
        startAutoplay();
      }
    }
  });

  function onTouchStart(e) {
    if (!e.touches || !e.touches.length) {
      return;
    }
    touchStartX = e.touches[0].clientX;
  }

  function onTouchEnd(e) {
    if (slides.length < 2 || !e.changedTouches || !e.changedTouches.length) {
      return;
    }
    var delta = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(delta) < 40) {
      return;
    }
    goTo(delta < 0 ? index + 1 : index - 1);
    startAutoplay();
  }

  hero.addEventListener('touchstart', onTouchStart, { passive: true });
  hero.addEventListener('touchend', onTouchEnd, { passive: true });

  sliderRoot.addEventListener('mouseenter', stopAutoplay);
  sliderRoot.addEventListener('mouseleave', startAutoplay);
  if (sliderUi) {
    sliderUi.addEventListener('mouseenter', stopAutoplay);
    sliderUi.addEventListener('mouseleave', startAutoplay);
  }

  goTo(0);
  startAutoplay();
})();
