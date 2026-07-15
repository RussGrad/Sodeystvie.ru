/**
 * Ипотечный квиз на главной: 4 шага → заявка в CRM.
 */
(function () {
  'use strict';

  var form = document.getElementById('mortgage-quiz-form');
  if (!form) {
    return;
  }

  var steps = Array.prototype.slice.call(form.querySelectorAll('[data-quiz-step]'));
  var progress = document.getElementById('mortgage-quiz-progress');
  var btnBack = document.getElementById('mortgage-quiz-back');
  var btnNext = document.getElementById('mortgage-quiz-next');
  var btnSubmit = document.getElementById('mortgage-quiz-submit');
  var errorEl = document.getElementById('mortgage-quiz-error');
  var successEl = document.getElementById('mortgage-quiz-success');
  var phoneInput = document.getElementById('mortgage-quiz-phone');
  var nameInput = document.getElementById('mortgage-quiz-name');
  var honeypotInput = document.getElementById('mortgage-quiz-company');
  var current = 0;
  var answers = {};

  function normalizePhoneDigits(value) {
    var d = String(value || '').replace(/\D/g, '');
    if (d.charAt(0) === '8') {
      d = '7' + d.slice(1);
    }
    if (d.charAt(0) !== '7') {
      d = '7' + d;
    }
    return d.slice(0, 11);
  }

  function formatRuPhone(digits) {
    var d = normalizePhoneDigits(digits);
    if (d.length <= 1) {
      return '+7';
    }
    var rest = d.slice(1);
    var out = '+7';
    if (rest.length > 0) {
      out += ' (' + rest.slice(0, 3);
    }
    if (rest.length >= 3) {
      out += ')';
    }
    if (rest.length > 3) {
      out += ' ' + rest.slice(3, 6);
    }
    if (rest.length > 6) {
      out += '-' + rest.slice(6, 8);
    }
    if (rest.length > 8) {
      out += '-' + rest.slice(8, 10);
    }
    return out;
  }

  if (phoneInput instanceof HTMLInputElement) {
    phoneInput.addEventListener('input', function () {
      phoneInput.value = formatRuPhone(phoneInput.value);
    });
  }

  function showStep(index) {
    current = Math.max(0, Math.min(index, steps.length - 1));
    for (var i = 0; i < steps.length; i++) {
      steps[i].hidden = i !== current;
      steps[i].classList.toggle('is-active', i === current);
    }
    if (progress) {
      progress.style.width = String(((current + 1) / steps.length) * 100) + '%';
    }
    if (btnBack) {
      btnBack.hidden = current === 0;
    }
    if (btnNext) {
      btnNext.hidden = current === steps.length - 1;
    }
    if (btnSubmit) {
      btnSubmit.hidden = current !== steps.length - 1;
    }
  }

  function getChecked(name) {
    var el = form.querySelector('input[name="' + name + '"]:checked');
    return el ? el.value : '';
  }

  function validateStep() {
    if (current === 0) {
      return getChecked('property') !== '';
    }
    if (current === 1) {
      return getChecked('down') !== '';
    }
    if (current === 2) {
      return getChecked('matcap') !== '';
    }
    if (current === 3) {
      var name = nameInput instanceof HTMLInputElement ? nameInput.value.trim() : '';
      var phone = phoneInput instanceof HTMLInputElement ? normalizePhoneDigits(phoneInput.value) : '';
      return name !== '' && phone.length >= 11;
    }
    return true;
  }

  function collectAnswers() {
    answers.property = getChecked('property');
    answers.down = getChecked('down');
    answers.matcap = getChecked('matcap');
  }

  function buildMessage() {
    var map = {
      new: 'Новостройка',
      resale: 'Вторичка',
      house: 'Дом/участок',
      '0-20': 'Взнос до 20%',
      '20-30': 'Взнос 20–30%',
      '30+': 'Взнос 30%+',
      yes: 'Маткапитал: да',
      no: 'Маткапитал: нет',
      maybe: 'Маткапитал: уточнить',
    };
    return [
      'Ипотечный квиз',
      'Объект: ' + (map[answers.property] || answers.property),
      'Взнос: ' + (map[answers.down] || answers.down),
      map[answers.matcap] || answers.matcap,
    ].join(' · ');
  }

  if (btnNext) {
    btnNext.addEventListener('click', function () {
      if (!validateStep()) {
        if (errorEl) {
          errorEl.textContent = 'Выберите вариант, чтобы продолжить';
          errorEl.hidden = false;
        }
        return;
      }
      if (errorEl) {
        errorEl.hidden = true;
      }
      collectAnswers();
      showStep(current + 1);
    });
  }

  if (btnBack) {
    btnBack.addEventListener('click', function () {
      if (errorEl) {
        errorEl.hidden = true;
      }
      showStep(current - 1);
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (honeypotInput instanceof HTMLInputElement && honeypotInput.value.trim() !== '') {
      return;
    }

    if (!validateStep()) {
      if (errorEl) {
        errorEl.textContent = 'Укажите имя и телефон';
        errorEl.hidden = false;
      }
      return;
    }

    collectAnswers();
    if (errorEl) {
      errorEl.hidden = true;
    }
    if (btnSubmit instanceof HTMLButtonElement) {
      btnSubmit.disabled = true;
    }

    var payload = {
      name: nameInput instanceof HTMLInputElement ? nameInput.value.trim().slice(0, 120) : '',
      phone: phoneInput instanceof HTMLInputElement ? normalizePhoneDigits(phoneInput.value) : '',
      pageUrl: window.location.pathname + window.location.search,
      topic: 'mortgage-quiz',
      message: buildMessage(),
      company: honeypotInput instanceof HTMLInputElement ? honeypotInput.value : '',
    };

    fetch('/api/lead-submit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, data: data };
        });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.ok) {
          form.querySelectorAll('fieldset').forEach(function (fs) {
            fs.hidden = true;
          });
          if (btnBack) {
            btnBack.hidden = true;
          }
          if (btnNext) {
            btnNext.hidden = true;
          }
          if (btnSubmit) {
            btnSubmit.hidden = true;
          }
          if (successEl) {
            successEl.hidden = false;
          }
          return;
        }
        throw new Error((result.data && result.data.error) || 'Ошибка отправки');
      })
      .catch(function (err) {
        if (errorEl) {
          errorEl.textContent = err.message || 'Не удалось отправить. Попробуйте позже.';
          errorEl.hidden = false;
        }
        if (btnSubmit instanceof HTMLButtonElement) {
          btnSubmit.disabled = false;
        }
      });
  });

  showStep(0);
})();
