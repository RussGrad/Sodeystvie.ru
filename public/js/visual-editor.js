(function () {
  'use strict';

  var bootEl = document.getElementById('ve-boot');
  if (!bootEl) {
    return;
  }

  var boot;
  try {
    boot = JSON.parse(bootEl.textContent || '{}');
  } catch (e) {
    return;
  }

  document.body.classList.add('ve-on');

  var bar = document.createElement('div');
  bar.className = 've-bar';
  bar.innerHTML =
    '<div>' +
    '<p class="ve-bar__title">Визуальный редактор</p>' +
    '<p class="ve-bar__hint">Кликните по подсвеченному элементу, чтобы изменить текст или логотип</p>' +
    '</div>' +
    '<div class="ve-bar__actions">' +
    '<p class="ve-bar__status" id="ve-status" aria-live="polite"></p>' +
    '<a class="ve-bar__btn ve-bar__btn--ghost" href="/">Смотреть сайт</a>' +
    '<a class="ve-bar__btn" href="' + (boot.exitUrl || '/admin/') + '">В админку</a>' +
    '<a class="ve-bar__btn ve-bar__btn--primary" href="/?ve=1">Обновить</a>' +
    '</div>';
  document.body.prepend(bar);

  var panel = document.createElement('aside');
  panel.className = 've-panel';
  panel.hidden = true;
  panel.innerHTML =
    '<h2 class="ve-panel__title" id="ve-panel-title">Редактирование</h2>' +
    '<p class="ve-panel__error" id="ve-panel-error" hidden></p>' +
    '<label class="ve-panel__label" for="ve-panel-input" id="ve-panel-label">Значение</label>' +
    '<input class="ve-panel__input" id="ve-panel-input" type="text">' +
    '<textarea class="ve-panel__textarea" id="ve-panel-textarea" hidden></textarea>' +
    '<input class="ve-panel__input" id="ve-panel-file" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" hidden>' +
    '<div class="ve-panel__actions">' +
    '<button type="button" class="ve-bar__btn ve-bar__btn--primary" id="ve-panel-save">Сохранить</button>' +
    '<button type="button" class="ve-bar__btn" id="ve-panel-cancel">Отмена</button>' +
    '</div>';
  document.body.appendChild(panel);

  var statusEl = document.getElementById('ve-status');
  var titleEl = document.getElementById('ve-panel-title');
  var labelEl = document.getElementById('ve-panel-label');
  var inputEl = document.getElementById('ve-panel-input');
  var textareaEl = document.getElementById('ve-panel-textarea');
  var fileEl = document.getElementById('ve-panel-file');
  var errorEl = document.getElementById('ve-panel-error');
  var saveBtn = document.getElementById('ve-panel-save');
  var cancelBtn = document.getElementById('ve-panel-cancel');

  var activeEl = null;
  var activeField = '';
  var activeType = 'text';

  function setStatus(msg) {
    if (statusEl) {
      statusEl.textContent = msg || '';
    }
  }

  function setError(msg) {
    if (!errorEl) {
      return;
    }
    if (!msg) {
      errorEl.hidden = true;
      errorEl.textContent = '';
      return;
    }
    errorEl.hidden = false;
    errorEl.textContent = msg;
  }

  function closePanel() {
    panel.hidden = true;
    if (activeEl) {
      activeEl.classList.remove('is-ve-active');
    }
    activeEl = null;
    activeField = '';
    setError('');
  }

  function openPanel(el) {
    var field = el.getAttribute('data-ve-field') || '';
    var type = el.getAttribute('data-ve-type') || 'text';
    var label = el.getAttribute('data-ve-label') || field;
    if (!field) {
      return;
    }

    if (activeEl) {
      activeEl.classList.remove('is-ve-active');
    }
    activeEl = el;
    activeField = field;
    activeType = type;
    el.classList.add('is-ve-active');

    titleEl.textContent = label;
    labelEl.textContent = label;
    setError('');

    inputEl.hidden = true;
    textareaEl.hidden = true;
    fileEl.hidden = true;

    if (type === 'image') {
      fileEl.hidden = false;
      labelEl.setAttribute('for', 've-panel-file');
    } else if (type === 'textarea') {
      textareaEl.hidden = false;
      textareaEl.value = (el.textContent || '').trim();
      labelEl.setAttribute('for', 've-panel-textarea');
      textareaEl.focus();
    } else {
      inputEl.hidden = false;
      inputEl.type = type === 'email' || type === 'tel' || type === 'url' ? type : 'text';
      inputEl.value = (el.textContent || '').trim();
      labelEl.setAttribute('for', 've-panel-input');
      inputEl.focus();
      inputEl.select();
    }

    panel.hidden = false;
  }

  document.addEventListener(
    'click',
    function (event) {
      var target = event.target;
      if (!(target instanceof Element)) {
        return;
      }
      if (panel.contains(target) || bar.contains(target)) {
        return;
      }
      var el = target.closest('[data-ve-field]');
      if (!el) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      openPanel(el);
    },
    true
  );

  cancelBtn.addEventListener('click', closePanel);

  saveBtn.addEventListener('click', function () {
    if (!activeField) {
      return;
    }

    setError('');
    setStatus('Сохранение…');
    saveBtn.disabled = true;

    if (activeType === 'image') {
      var file = fileEl.files && fileEl.files[0];
      if (!file) {
        setError('Выберите файл');
        setStatus('');
        saveBtn.disabled = false;
        return;
      }
      var fd = new FormData();
      fd.append('csrf', boot.csrf || '');
      fd.append('action', 'upload_logo');
      fd.append('logo', file);
      fetch(boot.saveUrl || '/admin/api/visual-save.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
      })
        .then(function (r) {
          return r.json().then(function (data) {
            return { ok: r.ok, data: data };
          });
        })
        .then(function (res) {
          if (!res.ok || !res.data || !res.data.ok) {
            throw new Error((res.data && res.data.error) || 'Ошибка сохранения');
          }
          setStatus('Логотип сохранён');
          window.location.reload();
        })
        .catch(function (err) {
          setError(err.message || 'Ошибка');
          setStatus('');
        })
        .finally(function () {
          saveBtn.disabled = false;
        });
      return;
    }

    var value = activeType === 'textarea' ? textareaEl.value : inputEl.value;
    fetch(boot.saveUrl || '/admin/api/visual-save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        csrf: boot.csrf || '',
        action: 'save_field',
        field: activeField,
        value: value,
      }),
    })
      .then(function (r) {
        return r.json().then(function (data) {
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (!res.ok || !res.data || !res.data.ok) {
          throw new Error((res.data && res.data.error) || 'Ошибка сохранения');
        }
        if (activeEl) {
          activeEl.textContent = res.data.value;
        }
        setStatus('Сохранено');
        closePanel();
      })
      .catch(function (err) {
        setError(err.message || 'Ошибка');
        setStatus('');
      })
      .finally(function () {
        saveBtn.disabled = false;
      });
  });
})();
