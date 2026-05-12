/**
 * Ипотечный калькулятор на странице /mortgage/.
 */
(function () {
    'use strict';

    var form = document.getElementById('mortgage-calc-form');
    if (!form) {
        return;
    }

    var outLoan = document.getElementById('mortgage-loan');
    var outMonthly = document.getElementById('mortgage-monthly');
    var outOverpay = document.getElementById('mortgage-overpay');
    var outTotal = document.getElementById('mortgage-total');

    function num(v) {
        var n = Number(v);
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

    // Аннуитет: P * (i * (1+i)^n) / ((1+i)^n - 1)
    function calc() {
        var price = num(form.elements.price && form.elements.price.value);
        var down = num(form.elements.down && form.elements.down.value);
        var years = num(form.elements.years && form.elements.years.value);
        var rate = num(form.elements.rate && form.elements.rate.value);

        if (down > price) {
            down = price;
        }

        var loan = Math.max(0, price - down);
        var months = Math.max(1, Math.round(years * 12));
        var i = Math.max(0, rate) / 100 / 12;

        var monthly = 0;
        if (loan === 0) {
            monthly = 0;
        } else if (i === 0) {
            monthly = loan / months;
        } else {
            var p = Math.pow(1 + i, months);
            monthly = loan * (i * p) / (p - 1);
        }

        var total = monthly * months;
        var overpay = Math.max(0, total - loan);

        if (outLoan) outLoan.textContent = fmtRub(loan);
        if (outMonthly) outMonthly.textContent = fmtRub(monthly);
        if (outOverpay) outOverpay.textContent = fmtRub(overpay);
        if (outTotal) outTotal.textContent = fmtRub(total);
    }

    form.addEventListener('input', calc);
    calc();
})();

