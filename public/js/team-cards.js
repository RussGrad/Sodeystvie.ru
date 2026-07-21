(function () {
  'use strict';

  var cards = document.querySelectorAll('[data-team-card]');
  if (!cards.length) return;

  function collapseCard(card) {
    var desc = card.querySelector('[data-team-desc-short]');
    var toggle = card.querySelector('[data-team-toggle]');
    card.classList.remove('is-expanded');
    if (desc && desc.dataset.teamDescShort) {
      desc.textContent = desc.dataset.teamDescShort;
    }
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
      toggle.textContent = 'Подробнее';
    }
  }

  function expandCard(card) {
    cards.forEach(function (other) {
      if (other !== card) collapseCard(other);
    });
    var desc = card.querySelector('[data-team-desc-full]');
    var toggle = card.querySelector('[data-team-toggle]');
    card.classList.add('is-expanded');
    if (desc && desc.dataset.teamDescFull) {
      desc.textContent = desc.dataset.teamDescFull;
    }
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'true');
      toggle.textContent = 'Свернуть';
    }
  }

  function toggleCard(card) {
    if (card.classList.contains('is-expanded')) {
      collapseCard(card);
    } else {
      expandCard(card);
    }
  }

  cards.forEach(function (card) {
    card.addEventListener('click', function (event) {
      var target = event.target;
      if (!(target instanceof Element)) return;
      if (target.closest('a')) return;
      if (target.closest('[data-ve-field]')) return;
      toggleCard(card);
    });

    card.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      var target = event.target;
      if (!(target instanceof Element)) return;
      if (target.closest('a, button')) return;
      event.preventDefault();
      toggleCard(card);
    });
  });

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!(target instanceof Element)) return;
    if (target.closest('[data-team-card]')) return;
    cards.forEach(collapseCard);
  });
})();
