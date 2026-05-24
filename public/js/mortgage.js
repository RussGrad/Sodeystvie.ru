/**
 * Страница /mortgage/: калькулятор (референс etagi), программы, банки, маски сумм.
 */
(function () {
  'use strict';

  var form = document.getElementById('mortgage-calc-form');
  if (!form) {
    return;
  }

  var PROGRAMS = {
    base: { rate: 16.9, label: 'Для всех' },
    family: { rate: 5.89, label: 'Семейная ипотека' },
    own: { rate: 11.9, label: 'Своя ставка' },
    rural: { rate: 3.0, label: 'Сельская ипотека' },
    it: { rate: 5.0, label: 'Ипотека для IT' },
    military: { rate: 17.1, label: 'Военная ипотека' },
  };

  var banksRoot = document.querySelector('.mortgage-banks');
  var banksAssetsBase =
    (banksRoot && banksRoot.getAttribute('data-banks-assets')) || '/assets/banks/';

  /** @type {Array<{id?: string, name: string, abbr: string, logo?: string, rateDelta: number}>} */
  var BANKS = loadBanks();

  function loadBanks() {
    var el = document.getElementById('mortgage-banks-data');
    if (el && el.textContent) {
      try {
        var parsed = JSON.parse(el.textContent);
        if (Array.isArray(parsed) && parsed.length > 0) {
          return parsed.map(normalizeBank).filter(Boolean);
        }
      } catch (e) {
        /* fallback */
      }
    }
    return defaultBanks();
  }

  function defaultBanks() {
    return [
      { id: 'tbank', name: 'Т-Банк', abbr: 'Т', logo: '', rateDelta: 0 },
      { id: 'metallinvest', name: 'Металлинвестбанк', abbr: 'М', logo: '', rateDelta: 0.15 },
      { id: 'domrf', name: 'ДОМ.РФ', abbr: 'Д', logo: '', rateDelta: 0.05 },
      { id: 'mkb', name: 'Московский кредитный банк', abbr: 'МКБ', logo: '', rateDelta: 0.2 },
      { id: 'uralsib', name: 'Уралсиб', abbr: 'У', logo: '', rateDelta: 0.25 },
      { id: 'sber', name: 'Сбер', abbr: 'С', logo: '', rateDelta: 0.1 },
    ];
  }

  /** @param {unknown} row */
  function normalizeBank(row) {
    if (!row || typeof row !== 'object') return null;
    var r = /** @type {{name?: string, abbr?: string, logo?: string, rateDelta?: number, delta?: number}} */ (row);
    var name = String(r.name || '').trim();
    if (!name) return null;
    return {
      id: String(/** @type {{id?: string}} */ (row).id || '').trim(),
      name: name,
      abbr: String(r.abbr || '').trim() || name.charAt(0),
      logo: String(r.logo || '').trim(),
      rateDelta: Number(r.rateDelta != null ? r.rateDelta : r.delta) || 0,
    };
  }

  function bankLogoUrl(filename) {
    var base = String(banksAssetsBase || '/assets/banks/').replace(/\/?$/, '/');
    var safe = String(filename).replace(/[^a-zA-Z0-9._-]/g, '');
    return base + safe;
  }

  /** @param {{name: string, abbr: string, logo?: string}} bank */
  function appendBankLogo(card, bank) {
    var wrap = document.createElement('div');
    wrap.className = 'mortgage-banks__logo';

    var fallback = document.createElement('span');
    fallback.className = 'mortgage-banks__logo-fallback';
    fallback.setAttribute('aria-hidden', 'true');
    fallback.textContent = bank.abbr || bank.name.charAt(0);

    if (bank.logo) {
      var img = document.createElement('img');
      img.className = 'mortgage-banks__logo-img';
      img.src = bankLogoUrl(bank.logo);
      img.alt = bank.name;
      img.width = 80;
      img.height = 40;
      img.loading = 'lazy';
      img.decoding = 'async';
      img.addEventListener('error', function () {
        img.remove();
        fallback.classList.remove('is-hidden');
      });
      img.addEventListener('load', function () {
        fallback.classList.add('is-hidden');
      });
      wrap.appendChild(img);
      if (img.complete && img.naturalWidth > 0) {
        fallback.classList.add('is-hidden');
      }
    }

    wrap.appendChild(fallback);
    card.appendChild(wrap);
  }

  var activeProgram = 'base';
  var syncDownLock = false;

  var priceEl = document.getElementById('mortgage-price');
  var downAmountEl = document.getElementById('mortgage-down-amount');
  var downPercentEl = document.getElementById('mortgage-down-percent');
  var yearsEl = document.getElementById('mortgage-years');
  var rateEl = document.getElementById('mortgage-rate');
  var rateField = document.getElementById('mortgage-rate-field');
  var detailFields = document.getElementById('mortgage-detail-fields');
  var banksTrack = document.getElementById('mortgage-banks-track');
  var propertyTypeEl = document.getElementById('mortgage-property-type');
  var socialProgramEl = document.getElementById('mortgage-social-program');

  var outLoan = document.getElementById('mortgage-loan');
  var outMonthly = document.getElementById('mortgage-monthly');
  var outOverpay = document.getElementById('mortgage-overpay');

  function onlyDigits(value) {
    return String(value || '').replace(/\D/g, '');
  }

  function formatMoneySpaces(value) {
    var digits = onlyDigits(value);
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }

  function parseNum(value) {
    var n = Number(String(value || '').replace(/\s/g, '').replace(',', '.'));
    return Number.isFinite(n) ? n : 0;
  }

  function fmtRub(v) {
    var n = Math.round(v);
    try {
      return new Intl.NumberFormat('ru-RU').format(n) + ' ₽';
    } catch (e) {
      return String(n) + ' ₽';
    }
  }

  function fmtRubShort(v) {
    var n = Math.round(v);
    try {
      return 'от ' + new Intl.NumberFormat('ru-RU').format(n) + ' ₽/мес.';
    } catch (e) {
      return 'от ' + n + ' ₽/мес.';
    }
  }

  function fmtRatePercent(rate) {
    var r = Math.round(rate * 100) / 100;
    var s = r.toFixed(2).replace(/\.?0+$/, '').replace('.', ',');
    return s;
  }

  function getPrice() {
    return parseNum(priceEl && priceEl.value);
  }

  function getYears() {
    var y = parseNum(yearsEl && yearsEl.value);
    return Math.min(40, Math.max(1, Math.round(y) || 1));
  }

  function getRate() {
    if (rateEl && rateField && !rateField.hidden) {
      return Math.max(0, parseNum(rateEl.value));
    }
    var p = PROGRAMS[activeProgram];
    return p ? p.rate : 16.9;
  }

  function annuityMonthly(loan, rateYear, years) {
    var months = Math.max(1, Math.round(years * 12));
    var i = Math.max(0, rateYear) / 100 / 12;
    if (loan <= 0) return 0;
    if (i === 0) return loan / months;
    var p = Math.pow(1 + i, months);
    return loan * (i * p) / (p - 1);
  }

  function syncDownFromPercent() {
    if (syncDownLock || !downAmountEl || !downPercentEl) return;
    syncDownLock = true;
    var price = getPrice();
    var pct = Math.min(100, Math.max(0, parseNum(downPercentEl.value)));
    downPercentEl.value = pct ? String(Math.round(pct)) : '';
    var amount = Math.round((price * pct) / 100);
    downAmountEl.value = formatMoneySpaces(amount);
    syncDownLock = false;
  }

  function syncDownFromAmount() {
    if (syncDownLock || !downAmountEl || !downPercentEl) return;
    syncDownLock = true;
    var price = getPrice();
    var amount = parseNum(downAmountEl.value);
    if (amount > price) amount = price;
    downAmountEl.value = formatMoneySpaces(amount);
    var pct = price > 0 ? Math.round((amount / price) * 100) : 0;
    downPercentEl.value = String(pct);
    syncDownLock = false;
  }

  function bindMoneyInput(el, onChange) {
    if (!(el instanceof HTMLInputElement)) return;
    el.addEventListener('input', function () {
      el.value = formatMoneySpaces(el.value);
      if (onChange) onChange();
      calc();
    });
  }

  function renderBanks(baseRate, loan, years) {
    if (!banksTrack) return;
    while (banksTrack.firstChild) {
      banksTrack.removeChild(banksTrack.firstChild);
    }
    BANKS.forEach(function (bank) {
      var rate = baseRate + bank.rateDelta;
      var monthly = annuityMonthly(loan, rate, years);
      var card = document.createElement('article');
      card.className = 'mortgage-banks__card';
      if (bank.id) card.dataset.bankId = bank.id;

      appendBankLogo(card, bank);

      var nameEl = document.createElement('p');
      nameEl.className = 'mortgage-banks__name';
      nameEl.textContent = bank.name;
      card.appendChild(nameEl);

      var payEl = document.createElement('p');
      payEl.className = 'mortgage-banks__payment';
      payEl.textContent = fmtRubShort(monthly);
      card.appendChild(payEl);

      var rateEl = document.createElement('p');
      rateEl.className = 'mortgage-banks__rate';
      rateEl.textContent = 'от ' + fmtRatePercent(rate) + '%';
      card.appendChild(rateEl);

      banksTrack.appendChild(card);
    });
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function calc() {
    var price = getPrice();
    var down = parseNum(downAmountEl && downAmountEl.value);
    if (down > price) down = price;
    var years = getYears();
    var rate = getRate();
    var loan = Math.max(0, price - down);
    var monthly = annuityMonthly(loan, rate, years);
    var months = Math.max(1, Math.round(years * 12));
    var total = monthly * months;
    var overpay = Math.max(0, total - loan);

    if (outLoan) outLoan.textContent = fmtRub(loan);
    if (outMonthly) outMonthly.textContent = fmtRub(monthly);
    if (outOverpay) outOverpay.textContent = fmtRub(overpay);

    renderBanks(rate, loan, years);
  }

  function setProgram(id) {
    if (!PROGRAMS[id]) return;
    activeProgram = id;
    document.querySelectorAll('.mortgage-programs__tab').forEach(function (btn) {
      var on = btn.getAttribute('data-program') === id;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    if (rateEl && rateField && rateField.hidden) {
      rateEl.value = String(PROGRAMS[id].rate).replace('.', ',');
    }
    if (socialProgramEl && detailFields && !detailFields.hidden) {
      var map = { base: 'base', family: 'family', rural: 'rural', it: 'it' };
      if (map[id]) socialProgramEl.value = map[id] || 'base';
    }
    calc();
  }

  function setMode(mode) {
    var detail = mode === 'detail';
    var calcRoot = document.querySelector('.mortgage-calc');
    if (calcRoot) {
      calcRoot.classList.toggle('is-detail', detail);
      calcRoot.classList.toggle('is-fast', !detail);
    }
    if (detailFields) detailFields.hidden = !detail;
    if (rateField) rateField.hidden = !detail;
    document.querySelectorAll('.mortgage-calc__mode-btn').forEach(function (btn) {
      var on = btn.getAttribute('data-mode') === mode;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    if (!detail && rateEl) {
      rateEl.value = String(getRate()).replace('.', ',');
    }
    calc();
  }

  document.querySelectorAll('.mortgage-programs__tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
      setProgram(btn.getAttribute('data-program') || 'base');
    });
  });

  document.querySelectorAll('.mortgage-calc__mode-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      setMode(btn.getAttribute('data-mode') || 'fast');
    });
  });

  if (propertyTypeEl) {
    propertyTypeEl.addEventListener('change', calc);
  }
  if (socialProgramEl) {
    socialProgramEl.addEventListener('change', function () {
      var v = socialProgramEl.value;
      if (PROGRAMS[v]) setProgram(v);
    });
  }
  if (rateEl) {
    rateEl.addEventListener('input', calc);
  }
  if (yearsEl) {
    yearsEl.addEventListener('input', function () {
      yearsEl.value = onlyDigits(yearsEl.value).slice(0, 2);
      calc();
    });
  }

  bindMoneyInput(priceEl, syncDownFromPercent);
  bindMoneyInput(downAmountEl, syncDownFromAmount);
  if (downPercentEl) {
    downPercentEl.addEventListener('input', function () {
      downPercentEl.value = onlyDigits(downPercentEl.value).slice(0, 3);
      syncDownFromPercent();
      calc();
    });
  }

  var hash = (location.hash || '').replace(/^#/, '');
  if (PROGRAMS[hash]) {
    setProgram(hash);
  } else if (hash === 'newbuild' || hash === 'resale' || hash === 'house' || hash === 'build') {
    setMode('detail');
    if (propertyTypeEl) {
      propertyTypeEl.value =
        hash === 'resale' ? 'resale' : hash === 'house' || hash === 'build' ? 'house' : 'newbuild';
    }
  } else if (hash === 'maternity' || hash === 'family') {
    setProgram('family');
  }

  var tabByHash = document.getElementById(hash);
  if (tabByHash && tabByHash.classList.contains('mortgage-programs__tab')) {
    setProgram(tabByHash.getAttribute('data-program') || 'base');
  }

  syncDownFromPercent();
  calc();
})();
