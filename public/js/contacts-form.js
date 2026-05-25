/**
 * Форма обратной связи на странице «Контакты».
 */
(function () {
  'use strict';

  var form = document.getElementById('contacts-form');
  if (!form) return;

  var nameInput = document.getElementById('contacts-name');
  var emailInput = document.getElementById('contacts-email');
  var phoneInput = document.getElementById('contacts-phone');
  var regionInput = document.getElementById('contacts-region');
  var subjectInput = document.getElementById('contacts-subject');
  var messageInput = document.getElementById('contacts-message');
  var honeypotInput = document.getElementById('contacts-company');
  var errorMsg = document.getElementById('contacts-form-error');
  var successMsg = document.getElementById('contacts-form-success');
  var submitBtn = document.getElementById('contacts-form-submit');
  var recaptchaEl = form.querySelector('.g-recaptcha');

  function normalizePhoneDigits(value) {
    var d = String(value || '').replace(/\D/g, '');
    if (d.charAt(0) === '8') d = '7' + d.slice(1);
    if (d.charAt(0) !== '7') d = '7' + d;
    return d.slice(0, 11);
  }

  function formatRuPhone(digits) {
    var d = normalizePhoneDigits(digits);
    if (d.length <= 1) return '+7';
    var rest = d.slice(1);
    var out = '+7';
    if (rest.length > 0) out += ' (' + rest.slice(0, 3);
    if (rest.length >= 3) out += ') ' + rest.slice(3, 6);
    if (rest.length >= 6) out += '-' + rest.slice(6, 8);
    if (rest.length >= 8) out += '-' + rest.slice(8, 10);
    return out;
  }

  if (phoneInput instanceof HTMLInputElement) {
    phoneInput.addEventListener('input', function () {
      var pos = phoneInput.selectionStart;
      phoneInput.value = formatRuPhone(phoneInput.value);
      if (typeof pos === 'number') {
        phoneInput.setSelectionRange(phoneInput.value.length, phoneInput.value.length);
      }
    });
    phoneInput.addEventListener('focus', function () {
      if (!phoneInput.value.trim()) phoneInput.value = '+7';
    });
  }

  function getRecaptchaToken() {
    if (!recaptchaEl || typeof grecaptcha === 'undefined') return '';
    try {
      return grecaptcha.getResponse() || '';
    } catch (e) {
      return '';
    }
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (honeypotInput instanceof HTMLInputElement && honeypotInput.value.trim() !== '') {
      return;
    }

    if (phoneInput instanceof HTMLInputElement) {
      var digits = normalizePhoneDigits(phoneInput.value);
      var phoneFilled = digits.length > 1;
      if (phoneFilled && digits.length < 11) {
        phoneInput.setCustomValidity('Введите номер полностью: +7 и 10 цифр');
        phoneInput.reportValidity();
        phoneInput.focus();
        return;
      }
      phoneInput.setCustomValidity('');
    }

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    if (recaptchaEl && typeof grecaptcha !== 'undefined') {
      var token = getRecaptchaToken();
      if (!token) {
        if (errorMsg) {
          errorMsg.textContent = 'Подтвердите, что вы не робот';
          errorMsg.removeAttribute('hidden');
        }
        return;
      }
    }

    if (errorMsg) {
      errorMsg.setAttribute('hidden', '');
      errorMsg.textContent = '';
    }
    if (submitBtn instanceof HTMLButtonElement) submitBtn.disabled = true;

    var payload = {
      form: 'contacts',
      name: nameInput instanceof HTMLInputElement ? nameInput.value.trim().slice(0, 120) : '',
      email: emailInput instanceof HTMLInputElement ? emailInput.value.trim().slice(0, 160) : '',
      phone:
        phoneInput instanceof HTMLInputElement
          ? normalizePhoneDigits(phoneInput.value)
          : '',
      region: regionInput instanceof HTMLSelectElement ? regionInput.value : '',
      subject: subjectInput instanceof HTMLSelectElement ? subjectInput.value : '',
      message: messageInput instanceof HTMLTextAreaElement ? messageInput.value.trim().slice(0, 2000) : '',
      pageUrl: window.location.pathname + window.location.search,
      company: honeypotInput instanceof HTMLInputElement ? honeypotInput.value : '',
      recaptchaToken: recaptchaEl ? getRecaptchaToken() : '',
    };

    fetch('/api/lead-submit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) {
        return res
          .json()
          .catch(function () {
            return { ok: false, error: 'Ошибка ответа сервера' };
          })
          .then(function (data) {
            return { ok: res.ok, data: data };
          });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.ok) {
          form.setAttribute('hidden', '');
          if (successMsg) {
            successMsg.removeAttribute('hidden');
            successMsg.focus();
          }
          return;
        }
        var errText =
          (result.data && result.data.error) || 'Не удалось отправить. Попробуйте позже или позвоните нам.';
        if (errorMsg) {
          errorMsg.textContent = errText;
          errorMsg.removeAttribute('hidden');
        }
      })
      .catch(function () {
        if (errorMsg) {
          errorMsg.textContent = 'Сеть недоступна. Проверьте подключение и повторите.';
          errorMsg.removeAttribute('hidden');
        }
      })
      .finally(function () {
        if (submitBtn instanceof HTMLButtonElement) submitBtn.disabled = false;
      });
  });
})();
