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

  function withVe(url) {
    try {
      var u = new URL(url, location.origin);
      if (u.origin !== location.origin) {
        return url;
      }
      if (u.pathname.indexOf('/admin') === 0) {
        return u.pathname + u.search + u.hash;
      }
      u.searchParams.delete('ve');
      return u.pathname + u.search + u.hash;
    } catch (err) {
      return url;
    }
  }

  function withVeOff(url) {
    try {
      var u = new URL(url, location.origin);
      u.searchParams.set('ve', '0');
      return u.pathname + u.search + u.hash;
    } catch (err) {
      return '/?ve=0';
    }
  }

  document.body.classList.add('ve-on');

  var pagesHtml = '';
  if (Array.isArray(boot.pages) && boot.pages.length) {
    pagesHtml =
      '<nav class="ve-bar__pages" aria-label="Страницы редактора">' +
      boot.pages
        .map(function (p) {
          var href = p && p.href ? String(p.href) : '';
          var label = p && p.label ? String(p.label) : href;
          if (!href) {
            return '';
          }
          var pagePath = '';
          try {
            pagePath = new URL(href, location.origin).pathname.replace(/\/+$/, '') || '/';
          } catch (err) {
            pagePath = href;
          }
          var curPath = location.pathname.replace(/\/+$/, '') || '/';
          var active = pagePath === curPath;
          return (
            '<a class="ve-bar__page' +
            (active ? ' is-active' : '') +
            '" href="' +
            href +
            '">' +
            label +
            '</a>'
          );
        })
        .join('') +
      '</nav>';
  }

  var crmNote = boot.teamFromCrm
    ? '<p class="ve-bar__hint">Команда сейчас из CRM — карточки сотрудников на сайте не редактируются здесь</p>'
    : '';

  var bar = document.createElement('div');
  bar.className = 've-bar';
  bar.innerHTML =
    '<div>' +
    '<p class="ve-bar__title">Визуальный редактор</p>' +
    '<p class="ve-bar__hint">Клик по подсветке — правка. Переходы по сайту остаются в редакторе. «Смотреть сайт» — выход.</p>' +
    crmNote +
    pagesHtml +
    '</div>' +
    '<div class="ve-bar__actions">' +
    '<p class="ve-bar__status" id="ve-status" aria-live="polite"></p>' +
    '<a class="ve-bar__btn ve-bar__btn--ghost" data-ve-exit href="' +
    withVeOff(location.pathname) +
    '">Смотреть сайт</a>' +
    '<a class="ve-bar__btn" href="' +
    (boot.exitUrl || '/admin/') +
    '">В админку</a>' +
    '<button type="button" class="ve-bar__btn ve-bar__btn--primary" id="ve-reload">Обновить</button>' +
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
    '<select class="ve-panel__select" id="ve-panel-select" hidden></select>' +
    '<p class="ve-panel__hint" id="ve-panel-hint" hidden></p>' +
    '<input class="ve-panel__input" id="ve-panel-file" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" hidden>' +
    '<div class="ve-panel__tools" id="ve-panel-tools" hidden>' +
    '<button type="button" class="ve-bar__btn ve-panel__tool" id="ve-tool-rotate-left" title="Повернуть влево">↺</button>' +
    '<button type="button" class="ve-bar__btn ve-panel__tool" id="ve-tool-rotate-right" title="Повернуть вправо">↻</button>' +
    '<button type="button" class="ve-bar__btn ve-panel__tool" id="ve-tool-zoom-out" title="Уменьшить">−</button>' +
    '<button type="button" class="ve-bar__btn ve-panel__tool" id="ve-tool-zoom-in" title="Увеличить">+</button>' +
    '</div>' +
    '<p class="ve-panel__meta" id="ve-panel-meta" hidden></p>' +
    '<div class="ve-panel__actions">' +
    '<button type="button" class="ve-bar__btn ve-bar__btn--primary" id="ve-panel-save">Сохранить</button>' +
    '<button type="button" class="ve-bar__btn" id="ve-panel-delete-image" hidden>Удалить изображение</button>' +
    '<button type="button" class="ve-bar__btn" id="ve-panel-cancel">Отмена</button>' +
    '</div>';
  document.body.appendChild(panel);

  var statusEl = document.getElementById('ve-status');
  var titleEl = document.getElementById('ve-panel-title');
  var labelEl = document.getElementById('ve-panel-label');
  var inputEl = document.getElementById('ve-panel-input');
  var textareaEl = document.getElementById('ve-panel-textarea');
  var selectEl = document.getElementById('ve-panel-select');
  var hintEl = document.getElementById('ve-panel-hint');
  var fileEl = document.getElementById('ve-panel-file');
  var errorEl = document.getElementById('ve-panel-error');
  var saveBtn = document.getElementById('ve-panel-save');
  var deleteImageBtn = document.getElementById('ve-panel-delete-image');
  var cancelBtn = document.getElementById('ve-panel-cancel');
  var reloadBtn = document.getElementById('ve-reload');
  var toolsEl = document.getElementById('ve-panel-tools');
  var metaEl = document.getElementById('ve-panel-meta');
  var rotateLeftBtn = document.getElementById('ve-tool-rotate-left');
  var rotateRightBtn = document.getElementById('ve-tool-rotate-right');
  var zoomOutBtn = document.getElementById('ve-tool-zoom-out');
  var zoomInBtn = document.getElementById('ve-tool-zoom-in');

  var activeEl = null;
  var activeField = '';
  var activeType = 'text';
  var activeDataset = '';
  var activeItemId = '';
  var activeRotate = 0;
  var activeScale = 1;

  function magFieldToKind(field) {
    return field === 'magazine_logo' ? 'logo' : 'photo';
  }

  function magFieldToRotateKey(field) {
    return field === 'magazine_logo' ? 'magazine_logo_rotate' : 'magazine_photo_rotate';
  }

  function magFieldToScaleKey(field) {
    return field === 'magazine_logo' ? 'magazine_logo_scale' : 'magazine_photo_scale';
  }

  function updateMagMeta() {
    if (!metaEl) {
      return;
    }
    metaEl.textContent = activeRotate + '° · ×' + activeScale.toFixed(2);
  }

  function applyMagTransform() {
    if (!activeEl) {
      return;
    }
    activeEl.style.setProperty('--mag-rotate', activeRotate + 'deg');
    activeEl.style.setProperty('--mag-scale', String(activeScale));
    activeEl.setAttribute('data-ve-rotate', String(activeRotate));
    activeEl.setAttribute('data-ve-scale', String(activeScale));
    updateMagMeta();
  }

  function saveMagField(field, value) {
    return fetch(boot.saveUrl || '/admin/api/visual-save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        csrf: boot.csrf || '',
        action: 'save_field',
        field: field,
        value: value,
      }),
    }).then(function (r) {
      return r.json().then(function (data) {
        return { ok: r.ok, data: data };
      });
    });
  }

  function saveMagTransform() {
    return saveMagField(magFieldToRotateKey(activeField), String(activeRotate)).then(function (res) {
      if (!res.ok || !res.data || !res.data.ok) {
        throw new Error((res.data && res.data.error) || 'Ошибка сохранения поворота');
      }
      return saveMagField(magFieldToScaleKey(activeField), String(activeScale));
    }).then(function (res) {
      if (!res.ok || !res.data || !res.data.ok) {
        throw new Error((res.data && res.data.error) || 'Ошибка сохранения масштаба');
      }
    });
  }

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

  function fillIconSelect(current) {
    var icons = Array.isArray(boot.serviceIcons) ? boot.serviceIcons : [];
    selectEl.innerHTML = '';
    icons.forEach(function (item) {
      var opt = document.createElement('option');
      opt.value = item.id;
      opt.textContent = item.label + ' (' + item.id + ')';
      if (item.id === current) {
        opt.selected = true;
      }
      selectEl.appendChild(opt);
    });
  }

  function readCurrentValue(el, type) {
    var fromAttr = el.getAttribute('data-ve-value');
    if (fromAttr) {
      return fromAttr;
    }
    if (type === 'url' && el.tagName === 'A') {
      return el.getAttribute('href') || '';
    }
    return (el.textContent || '').trim();
  }

  function closePanel() {
    panel.hidden = true;
    if (activeEl) {
      activeEl.classList.remove('is-ve-active');
    }
    activeEl = null;
    activeField = '';
    activeDataset = '';
    activeItemId = '';
    activeRotate = 0;
    activeScale = 1;
    if (toolsEl) {
      toolsEl.hidden = true;
    }
    if (metaEl) {
      metaEl.hidden = true;
      metaEl.textContent = '';
    }
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
    activeDataset = el.getAttribute('data-ve-dataset') || '';
    activeItemId = el.getAttribute('data-ve-item') || '';
    el.classList.add('is-ve-active');

    titleEl.textContent = label;
    labelEl.textContent = label;
    setError('');

    inputEl.hidden = true;
    textareaEl.hidden = true;
    selectEl.hidden = true;
    fileEl.hidden = true;
    if (hintEl) {
      hintEl.hidden = true;
      hintEl.textContent = '';
    }
    if (deleteImageBtn) {
      deleteImageBtn.hidden = true;
    }
    if (toolsEl) {
      toolsEl.hidden = true;
    }
    if (metaEl) {
      metaEl.hidden = true;
      metaEl.textContent = '';
    }
    fileEl.value = '';

    if (type === 'mag-image') {
      fileEl.hidden = false;
      labelEl.setAttribute('for', 've-panel-file');
      labelEl.textContent = 'Изображение';
      activeRotate = parseInt(el.getAttribute('data-ve-rotate') || '0', 10);
      if (Number.isNaN(activeRotate)) {
        activeRotate = 0;
      }
      activeScale = parseFloat(el.getAttribute('data-ve-scale') || '1');
      if (Number.isNaN(activeScale) || activeScale <= 0) {
        activeScale = 1;
      }
      applyMagTransform();
      if (toolsEl) {
        toolsEl.hidden = false;
      }
      if (metaEl) {
        metaEl.hidden = false;
      }
      if (deleteImageBtn && el.getAttribute('data-ve-has-image') === '1') {
        deleteImageBtn.hidden = false;
      }
    } else if (type === 'image') {
      fileEl.hidden = false;
      labelEl.setAttribute('for', 've-panel-file');
      if (deleteImageBtn && el.getAttribute('data-ve-has-image') === '1') {
        deleteImageBtn.hidden = false;
      }
    } else if (type === 'icon') {
      selectEl.hidden = false;
      fileEl.hidden = false;
      fillIconSelect(readCurrentValue(el, type) || 'realtor');
      labelEl.textContent = 'Встроенная иконка';
      labelEl.setAttribute('for', 've-panel-select');
      if (hintEl) {
        hintEl.hidden = false;
        hintEl.textContent = 'Или загрузите своё изображение ниже — оно заменит иконку.';
      }
      if (deleteImageBtn && el.getAttribute('data-ve-has-image') === '1') {
        deleteImageBtn.hidden = false;
      }
      selectEl.focus();
    } else if (type === 'textarea') {
      textareaEl.hidden = false;
      textareaEl.value = readCurrentValue(el, type);
      labelEl.setAttribute('for', 've-panel-textarea');
      textareaEl.focus();
    } else {
      inputEl.hidden = false;
      inputEl.type = type === 'email' || type === 'tel' || type === 'url' ? type : 'text';
      inputEl.value = readCurrentValue(el, type);
      labelEl.setAttribute('for', 've-panel-input');
      inputEl.focus();
      inputEl.select();
    }

    panel.hidden = false;
  }

  function shouldIgnoreLink(a) {
    if (!a || a.hasAttribute('data-ve-exit')) {
      return true;
    }
    if (a.getAttribute('target') === '_blank' && !a.hasAttribute('data-ve-field')) {
      return true;
    }
    if (a.hasAttribute('download')) {
      return true;
    }
    var href = a.getAttribute('href') || '';
    if (!href || href.charAt(0) === '#' || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) {
      return true;
    }
    try {
      var u = new URL(a.href, location.origin);
      if (u.origin !== location.origin) {
        return true;
      }
      if (u.pathname.indexOf('/admin') === 0) {
        return true;
      }
    } catch (err) {
      return true;
    }
    return false;
  }

  document.addEventListener(
    'click',
    function (event) {
      if (event.defaultPrevented) {
        return;
      }
      if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      var target = event.target;
      if (!(target instanceof Element)) {
        return;
      }
      if (panel.contains(target)) {
        return;
      }

      var addServiceBtn = target.closest('[data-ve-add-service]');
      if (addServiceBtn) {
        event.preventDefault();
        event.stopPropagation();
        createService();
        return;
      }

      var deleteServiceBtn = target.closest('[data-ve-delete-service]');
      if (deleteServiceBtn) {
        event.preventDefault();
        event.stopPropagation();
        deleteService(deleteServiceBtn.getAttribute('data-ve-delete-service') || '');
        return;
      }

      var veEl = target.closest('[data-ve-field]');
      if (veEl && !bar.contains(veEl)) {
        event.preventDefault();
        event.stopPropagation();
        openPanel(veEl);
        return;
      }

      if (bar.contains(target)) {
        return;
      }

      var a = target.closest('a[href]');
      if (!a || shouldIgnoreLink(a)) {
        return;
      }

      // Cookie уже держит VE: просто переходим без сброса режима
      event.preventDefault();
      event.stopPropagation();
      location.assign(withVe(a.href));
    },
    true
  );

  if (reloadBtn) {
    reloadBtn.addEventListener('click', function () {
      location.reload();
    });
  }

  cancelBtn.addEventListener('click', closePanel);

  function bindMagTool(btn, handler) {
    if (!btn) {
      return;
    }
    btn.addEventListener('click', function () {
      if (activeType !== 'mag-image' || !activeEl) {
        return;
      }
      handler();
      applyMagTransform();
    });
  }

  bindMagTool(rotateLeftBtn, function () {
    activeRotate = Math.max(-180, activeRotate - 5);
  });
  bindMagTool(rotateRightBtn, function () {
    activeRotate = Math.min(180, activeRotate + 5);
  });
  bindMagTool(zoomOutBtn, function () {
    activeScale = Math.max(0.5, Math.round((activeScale - 0.05) * 100) / 100);
  });
  bindMagTool(zoomInBtn, function () {
    activeScale = Math.min(2.5, Math.round((activeScale + 0.05) * 100) / 100);
  });

  if (deleteImageBtn) {
    deleteImageBtn.addEventListener('click', function () {
      var deleteDataset = activeDataset;
      var deleteId = activeItemId;
      if (activeType === 'mag-image') {
        deleteDataset = 'magazine';
        deleteId = magFieldToKind(activeField);
      } else if (!deleteDataset || !deleteId) {
        return;
      }
      setError('');
      setStatus('Удаление…');
      deleteImageBtn.disabled = true;
      saveBtn.disabled = true;
      fetch(boot.saveUrl || '/admin/api/visual-save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          csrf: boot.csrf || '',
          action: 'delete_image',
          dataset: deleteDataset,
          id: deleteId,
        }),
      })
        .then(function (r) {
          return r.json().then(function (data) {
            return { ok: r.ok, data: data };
          });
        })
        .then(function (res) {
          if (!res.ok || !res.data || !res.data.ok) {
            throw new Error((res.data && res.data.error) || 'Ошибка удаления');
          }
          setStatus('Изображение удалено');
          window.location.reload();
        })
        .catch(function (err) {
          setError(err.message || 'Ошибка');
          setStatus('');
        })
        .finally(function () {
          deleteImageBtn.disabled = false;
          saveBtn.disabled = false;
        });
    });
  }

  saveBtn.addEventListener('click', function () {
    if (!activeField) {
      return;
    }

    setError('');
    setStatus('Сохранение…');
    saveBtn.disabled = true;

    function uploadImageFile(file) {
      var fd = new FormData();
      fd.append('csrf', boot.csrf || '');
      if (activeDataset && activeItemId && (activeField === 'image' || activeType === 'icon')) {
        fd.append('action', 'upload_image');
        fd.append('dataset', activeDataset);
        fd.append('id', activeItemId);
        fd.append('field', 'image');
        fd.append('file', file);
      } else {
        fd.append('action', 'upload_logo');
        fd.append('logo', file);
      }
      return fetch(boot.saveUrl || '/admin/api/visual-save.php', {
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
          setStatus('Изображение сохранено');
          window.location.reload();
        });
    }

    if (activeType === 'mag-image') {
      var magFile = fileEl.files && fileEl.files[0];
      var uploadPromise = Promise.resolve();

      if (magFile) {
        var magFd = new FormData();
        magFd.append('csrf', boot.csrf || '');
        magFd.append('action', 'upload_image');
        magFd.append('dataset', 'magazine');
        magFd.append('id', magFieldToKind(activeField));
        magFd.append('file', magFile);
        uploadPromise = fetch(boot.saveUrl || '/admin/api/visual-save.php', {
          method: 'POST',
          body: magFd,
          credentials: 'same-origin',
        })
          .then(function (r) {
            return r.json().then(function (data) {
              return { ok: r.ok, data: data };
            });
          })
          .then(function (res) {
            if (!res.ok || !res.data || !res.data.ok) {
              throw new Error((res.data && res.data.error) || 'Ошибка загрузки');
            }
          });
      }

      uploadPromise
        .then(function () {
          return saveMagTransform();
        })
        .then(function () {
          setStatus('Сохранено');
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

    if (activeType === 'image') {
      var imageFile = fileEl.files && fileEl.files[0];
      if (!imageFile) {
        setError('Выберите файл');
        setStatus('');
        saveBtn.disabled = false;
        return;
      }
      uploadImageFile(imageFile)
        .catch(function (err) {
          setError(err.message || 'Ошибка');
          setStatus('');
        })
        .finally(function () {
          saveBtn.disabled = false;
        });
      return;
    }

    if (activeType === 'icon') {
      var iconFile = fileEl.files && fileEl.files[0];
      if (iconFile) {
        uploadImageFile(iconFile)
          .catch(function (err) {
            setError(err.message || 'Ошибка');
            setStatus('');
          })
          .finally(function () {
            saveBtn.disabled = false;
          });
        return;
      }
    }

    var value =
      activeType === 'textarea'
        ? textareaEl.value
        : activeType === 'icon'
          ? selectEl.value
          : inputEl.value;

    var payload = {
      csrf: boot.csrf || '',
      field: activeField,
      value: value,
    };

    if (activeDataset && activeItemId) {
      payload.action = 'save_item';
      payload.dataset = activeDataset;
      payload.id = activeItemId;
    } else {
      payload.action = 'save_field';
    }

    fetch(boot.saveUrl || '/admin/api/visual-save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
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
        if (activeType === 'icon' || activeType === 'url' || activeType === 'image') {
          setStatus(res.data.warning ? res.data.warning : 'Сохранено');
          window.location.reload();
          return;
        }
        if (activeEl) {
          activeEl.textContent = res.data.value;
          activeEl.setAttribute('data-ve-value', res.data.value);
        }
        setStatus(res.data.warning ? res.data.warning : 'Сохранено');
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

  function createService() {
    var title = window.prompt('Название новой услуги', 'Новая услуга');
    if (title === null) {
      return;
    }
    setStatus('Создание услуги…');
    fetch(boot.saveUrl || '/admin/api/visual-save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        csrf: boot.csrf || '',
        action: 'create_item',
        dataset: 'services',
        title: title,
      }),
    })
      .then(function (r) {
        return r.json().then(function (data) {
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (!res.ok || !res.data || !res.data.ok) {
          throw new Error((res.data && res.data.error) || 'Не удалось создать');
        }
        setStatus('Услуга добавлена');
        window.location.reload();
      })
      .catch(function (err) {
        setStatus('');
        window.alert(err.message || 'Ошибка');
      });
  }

  function deleteService(id) {
    if (!id) {
      return;
    }
    if (!window.confirm('Удалить эту услугу? Действие нельзя отменить.')) {
      return;
    }
    setStatus('Удаление…');
    fetch(boot.saveUrl || '/admin/api/visual-save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        csrf: boot.csrf || '',
        action: 'delete_item',
        dataset: 'services',
        id: id,
      }),
    })
      .then(function (r) {
        return r.json().then(function (data) {
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (!res.ok || !res.data || !res.data.ok) {
          throw new Error((res.data && res.data.error) || 'Не удалось удалить');
        }
        setStatus('Услуга удалена');
        window.location.reload();
      })
      .catch(function (err) {
        setStatus('');
        window.alert(err.message || 'Ошибка');
      });
  }
})();
