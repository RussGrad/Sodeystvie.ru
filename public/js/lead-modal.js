/**
 * Модальное окно заявки: открытие/закрытие, маска телефона +7, отправка формы.
 */
(function () {
  'use strict';

  var modal = document.getElementById('lead-modal');
  var openBtn = document.getElementById('site-header-lead-open');
  var form = document.getElementById('lead-form');
  var successMsg = document.getElementById('lead-form-success');
  var errorMsg = document.getElementById('lead-form-error');
  var submitBtn = document.getElementById('lead-form-submit');
  var phoneInput = document.getElementById('lead-phone');
  var nameInput = document.getElementById('lead-name');
  var honeypotInput = document.getElementById('lead-company');
  var pendingObjectId = '';
  var pendingTopic = '';
  var pendingMessage = '';

  if (!modal || !openBtn) {
    return;
  }

  var panel = modal.querySelector('.modal-lead__panel');
  var firstInput = document.getElementById('lead-name');
  var closeTimer = null;

  /**
   * @param {string} value
   * @returns {string} до 11 цифр, первая — 7
   */
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

  /**
   * @param {string} digits
   * @returns {string}
   */
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

  /**
   * @param {HTMLInputElement} input
   */
  function bindPhoneMask(input) {
    function syncFromInput() {
      var formatted = formatRuPhone(input.value);
      input.value = formatted;
      input.setCustomValidity('');
    }

    input.addEventListener('focus', function () {
      if (!input.value || input.value === '+7') {
        input.value = '+7 (';
      }
      window.setTimeout(function () {
        var len = input.value.length;
        input.setSelectionRange(len, len);
      }, 0);
    });

    input.addEventListener('blur', function () {
      var d = normalizePhoneDigits(input.value);
      if (d.length <= 1) {
        input.value = '';
      }
    });

    input.addEventListener('input', syncFromInput);

    input.addEventListener('keydown', function (e) {
      var key = e.key;
      if (
        key === 'Backspace' ||
        key === 'Delete' ||
        key === 'Tab' ||
        key === 'ArrowLeft' ||
        key === 'ArrowRight' ||
        key === 'Home' ||
        key === 'End' ||
        e.ctrlKey ||
        e.metaKey
      ) {
        return;
      }
      if (key.length === 1 && !/\d/.test(key)) {
        e.preventDefault();
      }
    });
  }

  if (phoneInput instanceof HTMLInputElement) {
    bindPhoneMask(phoneInput);
  }

  function isOpen() {
    return modal.classList.contains('modal-lead--open');
  }

  function open() {
    var header = document.getElementById('site-header');
    var burger = document.getElementById('site-header-burger');
    if (header) {
      header.classList.remove('site-header--nav-open');
    }
    document.body.classList.remove('site-header--nav-open');
    if (burger) {
      burger.setAttribute('aria-expanded', 'false');
      burger.setAttribute('aria-label', 'Открыть меню');
    }

    modal.removeAttribute('inert');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('has-modal');

    if (form) {
      form.removeAttribute('hidden');
      form.reset();
    }
    if (phoneInput instanceof HTMLInputElement) {
      phoneInput.value = '';
      phoneInput.setCustomValidity('');
    }
    if (successMsg) {
      successMsg.setAttribute('hidden', '');
    }
    if (errorMsg) {
      errorMsg.setAttribute('hidden', '');
      errorMsg.textContent = '';
    }
    if (submitBtn instanceof HTMLButtonElement) {
      submitBtn.disabled = false;
    }

    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () {
        modal.classList.add('modal-lead--open');
      });
    });

    window.setTimeout(function () {
      if (firstInput) {
        firstInput.focus();
      } else if (panel) {
        panel.focus();
      }
    }, 60);
  }

  function finishClose() {
    modal.setAttribute('aria-hidden', 'true');
    modal.setAttribute('inert', '');
    document.body.classList.remove('has-modal');
    openBtn.focus();
    closeTimer = null;
  }

  function close() {
    if (!modal.classList.contains('modal-lead--open')) {
      return;
    }
    modal.classList.remove('modal-lead--open');

    if (closeTimer) {
      window.clearTimeout(closeTimer);
    }

    var done = false;
    function end() {
      if (done) {
        return;
      }
      done = true;
      modal.removeEventListener('transitionend', onEnd);
      finishClose();
    }

    function onEnd(e) {
      if (e.target === panel && (e.propertyName === 'transform' || e.propertyName === 'opacity')) {
        end();
      }
    }

    modal.addEventListener('transitionend', onEnd);
    closeTimer = window.setTimeout(end, 400);
  }

  openBtn.addEventListener('click', function () {
    open();
  });

  document.addEventListener('click', function (e) {
    var t = e.target;
    if (!t || !t.closest) {
      return;
    }
    var opener = t.closest('[data-lead-open]');
    if (!opener) {
      return;
    }
    var objectId = opener.getAttribute('data-lead-object-id');
    pendingObjectId =
      typeof objectId === 'string' && objectId.trim() !== '' ? objectId.trim() : '';
    var topic = opener.getAttribute('data-lead-topic');
    pendingTopic = typeof topic === 'string' && topic.trim() !== '' ? topic.trim() : '';
    var message = opener.getAttribute('data-lead-message');
    pendingMessage = typeof message === 'string' && message.trim() !== '' ? message.trim() : '';
    e.preventDefault();
    open();
  });

  modal.addEventListener('click', function (e) {
    var t = e.target;
    if (t && t.closest && t.closest('[data-lead-modal-close]')) {
      close();
    }
  });

  document.addEventListener(
    'keydown',
    function (e) {
      if (e.key !== 'Escape' || !isOpen()) {
        return;
      }
      e.preventDefault();
      e.stopPropagation();
      close();
    },
    true,
  );

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      if (phoneInput instanceof HTMLInputElement) {
        var digits = normalizePhoneDigits(phoneInput.value);
        if (digits.length < 11) {
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

      if (honeypotInput instanceof HTMLInputElement && honeypotInput.value.trim() !== '') {
        return;
      }

      if (errorMsg) {
        errorMsg.setAttribute('hidden', '');
        errorMsg.textContent = '';
      }
      if (submitBtn instanceof HTMLButtonElement) {
        submitBtn.disabled = true;
      }

      var payload = {
        name: nameInput instanceof HTMLInputElement ? nameInput.value.trim().slice(0, 120) : '',
        phone:
          phoneInput instanceof HTMLInputElement
            ? normalizePhoneDigits(phoneInput.value)
            : '',
        pageUrl: window.location.pathname + window.location.search,
        company: honeypotInput instanceof HTMLInputElement ? honeypotInput.value : '',
      };
      if (pendingObjectId) {
        payload.objectId = pendingObjectId;
      }
      if (pendingTopic) {
        payload.topic = pendingTopic;
      }
      if (pendingMessage) {
        payload.message = pendingMessage;
      }

      fetch('/api/lead-submit.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
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
            (result.data && result.data.error) ||
            'Не удалось отправить заявку. Попробуйте позже или позвоните нам.';
          if (errorMsg) {
            errorMsg.textContent = errText;
            errorMsg.removeAttribute('hidden');
          }
          if (submitBtn instanceof HTMLButtonElement) {
            submitBtn.disabled = false;
          }
        })
        .catch(function () {
          if (errorMsg) {
            errorMsg.textContent =
              'Нет связи с сервером. Проверьте интернет и попробуйте снова.';
            errorMsg.removeAttribute('hidden');
          }
          if (submitBtn instanceof HTMLButtonElement) {
            submitBtn.disabled = false;
          }
        });
    });
  }
})();
