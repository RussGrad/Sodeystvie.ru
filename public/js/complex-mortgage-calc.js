(function () {
  const root = document.querySelector('[data-complex-mortgage-calc]');
  if (!root) return;

  const priceEl = root.querySelector('[data-mortgage-price]');
  const downPercentEl = root.querySelector('[data-mortgage-down-percent]');
  const yearsEl = root.querySelector('[data-mortgage-years]');
  const rateEl = root.querySelector('[data-mortgage-rate]');
  const monthlyEl = root.querySelector('[data-mortgage-monthly]');
  const loanEl = root.querySelector('[data-mortgage-loan]');

  function onlyDigits(value) {
    return String(value || '').replace(/\D/g, '');
  }

  function formatMoneySpaces(value) {
    const digits = onlyDigits(value);
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }

  function parseNum(value) {
    const n = Number(String(value || '').replace(/\s/g, '').replace(',', '.'));
    return Number.isFinite(n) ? n : 0;
  }

  function fmtRub(v) {
    const n = Math.round(v);
    try {
      return new Intl.NumberFormat('ru-RU').format(n) + ' ₽';
    } catch {
      return n + ' ₽';
    }
  }

  function annuityMonthly(loan, rateYear, years) {
    const months = Math.max(1, Math.round(years * 12));
    const i = Math.max(0, rateYear) / 100 / 12;
    if (loan <= 0) return 0;
    if (i === 0) return loan / months;
    const p = Math.pow(1 + i, months);
    return (loan * (i * p)) / (p - 1);
  }

  function recalc() {
    const price = parseNum(priceEl?.value);
    const downPct = Math.min(100, Math.max(0, parseNum(downPercentEl?.value)));
    const years = Math.min(40, Math.max(1, parseNum(yearsEl?.value) || 1));
    const rate = Math.max(0, parseNum(rateEl?.value));
    const loan = Math.max(0, price - (price * downPct) / 100);
    const monthly = annuityMonthly(loan, rate, years);
    if (monthlyEl) monthlyEl.textContent = fmtRub(monthly);
    if (loanEl) loanEl.textContent = fmtRub(loan);
  }

  function bindMoneyInput(el) {
    if (!(el instanceof HTMLInputElement)) return;
    el.addEventListener('input', () => {
      const pos = el.selectionStart;
      el.value = formatMoneySpaces(el.value);
      recalc();
      if (pos != null) el.setSelectionRange(el.value.length, el.value.length);
    });
  }

  bindMoneyInput(priceEl);
  [downPercentEl, yearsEl, rateEl].forEach((el) => {
    el?.addEventListener('input', recalc);
    el?.addEventListener('change', recalc);
  });

  recalc();
})();
