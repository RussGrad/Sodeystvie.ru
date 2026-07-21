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

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  function round1(n) {
    return Math.round(n * 10) / 10;
  }

  function round2(n) {
    return Math.round(n * 100) / 100;
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
    '<p class="ve-bar__hint">Клик по журналу, фото или плашке — правка на месте. Перетаскивайте выделенное. «Смотреть сайт» — выход.</p>' +
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

  var dock = document.createElement('div');
  dock.className = 've-dock';
  dock.hidden = true;
  dock.innerHTML =
    '<div class="ve-dock__head">' +
    '<strong class="ve-dock__title" id="ve-dock-title">Правка</strong>' +
    '<button type="button" class="ve-dock__close" id="ve-dock-close" title="Закрыть">×</button>' +
    '</div>' +
    '<p class="ve-dock__hint" id="ve-dock-hint">Стрелки и дуга — положение; перетаскивание тоже работает</p>' +
    '<div class="ve-dock__tools" id="ve-dock-tools" hidden>' +
    '<label class="ve-dock__btn ve-dock__btn--file" id="ve-dock-file-label" hidden>' +
    'Файл<input type="file" id="ve-dock-file" accept="image/png,image/jpeg,image/webp,image/svg+xml" hidden>' +
    '</label>' +
    '</div>' +
    '<p class="ve-dock__meta" id="ve-dock-meta"></p>' +
    '<textarea class="ve-dock__textarea" id="ve-dock-textarea" hidden rows="3"></textarea>' +
    '<p class="ve-dock__error" id="ve-dock-error" hidden></p>' +
    '<div class="ve-dock__actions">' +
    '<button type="button" class="ve-bar__btn ve-bar__btn--primary" id="ve-dock-save">Сохранить</button>' +
    '<button type="button" class="ve-bar__btn" id="ve-dock-delete" hidden>Удалить</button>' +
    '</div>';
  document.body.appendChild(dock);

  var gizmo = document.createElement('div');
  gizmo.className = 've-gizmo';
  gizmo.hidden = true;
  gizmo.setAttribute('aria-hidden', 'true');
  gizmo.innerHTML =
    '<svg class="ve-gizmo__arc" viewBox="0 0 200 200" aria-hidden="true">' +
    '<circle class="ve-gizmo__ring" cx="100" cy="100" r="78" fill="none" />' +
    '<path class="ve-gizmo__arc-path" d="M 30 100 A 70 70 0 0 1 170 100" fill="none" />' +
    '</svg>' +
    '<button type="button" class="ve-gizmo__btn ve-gizmo__btn--up" data-ve-gizmo="move-up" title="Вверх">↑</button>' +
    '<button type="button" class="ve-gizmo__btn ve-gizmo__btn--down" data-ve-gizmo="move-down" title="Вниз">↓</button>' +
    '<button type="button" class="ve-gizmo__btn ve-gizmo__btn--left" data-ve-gizmo="move-left" title="Влево">←</button>' +
    '<button type="button" class="ve-gizmo__btn ve-gizmo__btn--right" data-ve-gizmo="move-right" title="Вправо">→</button>' +
    '<button type="button" class="ve-gizmo__btn ve-gizmo__btn--rot-left" data-ve-gizmo="rotate-left" title="Повернуть влево">↺</button>' +
    '<button type="button" class="ve-gizmo__btn ve-gizmo__btn--rot-right" data-ve-gizmo="rotate-right" title="Повернуть вправо">↻</button>' +
    '<button type="button" class="ve-gizmo__btn ve-gizmo__btn--zoom-out" data-ve-gizmo="zoom-out" title="Уменьшить">−</button>' +
    '<button type="button" class="ve-gizmo__btn ve-gizmo__btn--zoom-in" data-ve-gizmo="zoom-in" title="Увеличить">+</button>' +
    '<span class="ve-gizmo__center" id="ve-gizmo-center"></span>';
  document.body.appendChild(gizmo);

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

  var dockTitle = document.getElementById('ve-dock-title');
  var dockHint = document.getElementById('ve-dock-hint');
  var dockTools = document.getElementById('ve-dock-tools');
  var dockMeta = document.getElementById('ve-dock-meta');
  var dockTextarea = document.getElementById('ve-dock-textarea');
  var dockError = document.getElementById('ve-dock-error');
  var dockSave = document.getElementById('ve-dock-save');
  var dockDelete = document.getElementById('ve-dock-delete');
  var dockClose = document.getElementById('ve-dock-close');
  var dockFile = document.getElementById('ve-dock-file');
  var dockFileLabel = document.getElementById('ve-dock-file-label');
  var gizmoCenter = document.getElementById('ve-gizmo-center');

  var activeEl = null;
  var activeField = '';
  var activeType = 'text';
  var activeDataset = '';
  var activeItemId = '';
  var activeRotate = 0;
  var activeScale = 1;
  var activeX = 0;
  var activeY = 0;
  var canvasMode = false;
  var dragState = null;
  var holdTimer = null;
  var holdInterval = null;

  function isCanvasType(type, field) {
    return (
      type === 'mag-image' ||
      type === 'mag-layout' ||
      type === 'mag-plate' ||
      field === 'home_lead_badge'
    );
  }

  function magFieldToKind(field) {
    return field === 'magazine_logo' ? 'logo' : 'photo';
  }

  function magFieldToRotateKey(field) {
    return field === 'magazine_logo' ? 'magazine_logo_rotate' : 'magazine_photo_rotate';
  }

  function magFieldToScaleKey(field) {
    return field === 'magazine_logo' ? 'magazine_logo_scale' : 'magazine_photo_scale';
  }

  function magFieldToXKey(field) {
    return field === 'magazine_logo' ? 'magazine_logo_x' : 'magazine_photo_x';
  }

  function magFieldToYKey(field) {
    return field === 'magazine_logo' ? 'magazine_logo_y' : 'magazine_photo_y';
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

  function setDockError(msg) {
    if (!dockError) {
      return;
    }
    if (!msg) {
      dockError.hidden = true;
      dockError.textContent = '';
      return;
    }
    dockError.hidden = false;
    dockError.textContent = msg;
  }

  function updateMagMeta() {
    var text = '';
    if (activeType === 'mag-plate') {
      text = 'отступ снизу: ' + Math.round(activeY) + 'px · можно перетащить';
    } else if (activeType === 'mag-layout' || activeType === 'mag-image') {
      text =
        Math.round(activeRotate) +
        '° · ×' +
        activeScale.toFixed(2) +
        ' · ' +
        round1(activeX) +
        '% / ' +
        round1(activeY) +
        '%';
    }
    if (metaEl) {
      metaEl.textContent = text;
    }
    if (dockMeta) {
      dockMeta.textContent = text;
    }
  }

  function applyCanvasTransform() {
    if (!activeEl) {
      return;
    }
    if (activeType === 'mag-image') {
      activeEl.style.setProperty('--mag-rotate', activeRotate + 'deg');
      activeEl.style.setProperty('--mag-scale', String(activeScale));
      activeEl.style.setProperty('--mag-x', activeX + '%');
      activeEl.style.setProperty('--mag-y', activeY + '%');
      activeEl.setAttribute('data-ve-rotate', String(activeRotate));
      activeEl.setAttribute('data-ve-scale', String(activeScale));
      activeEl.setAttribute('data-ve-x', String(activeX));
      activeEl.setAttribute('data-ve-y', String(activeY));
    } else if (activeType === 'mag-layout') {
      activeEl.style.setProperty('--mag-layout-rotate', activeRotate + 'deg');
      activeEl.style.setProperty('--mag-layout-scale', String(activeScale));
      activeEl.style.setProperty('--mag-layout-x', activeX + '%');
      activeEl.style.setProperty('--mag-layout-y', activeY + '%');
      activeEl.setAttribute('data-ve-rotate', String(activeRotate));
      activeEl.setAttribute('data-ve-scale', String(activeScale));
      activeEl.setAttribute('data-ve-x', String(activeX));
      activeEl.setAttribute('data-ve-y', String(activeY));
    } else if (activeType === 'mag-plate') {
      activeEl.style.setProperty('--mag-calc-offset', Math.round(activeY) + 'px');
      activeEl.setAttribute('data-ve-y', String(Math.round(activeY)));
    }
    updateMagMeta();
    positionDock();
    positionGizmo();
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

  function saveFieldsSequential(pairs) {
    var chain = Promise.resolve();
    pairs.forEach(function (pair) {
      chain = chain.then(function () {
        return saveMagField(pair[0], pair[1]).then(function (res) {
          if (!res.ok || !res.data || !res.data.ok) {
            throw new Error((res.data && res.data.error) || 'Ошибка сохранения');
          }
        });
      });
    });
    return chain;
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
    if (type === 'mag-plate') {
      var textNode = el.querySelector('.mortgage-quiz__calc-link-text');
      return ((textNode && textNode.textContent) || el.textContent || '').trim();
    }
    return (el.textContent || '').trim();
  }

  function positionGizmo() {
    if (!canvasMode || !activeEl || gizmo.hidden) {
      return;
    }
    var rect = activeEl.getBoundingClientRect();
    var size = activeType === 'mag-plate' ? 168 : 220;
    gizmo.style.width = size + 'px';
    gizmo.style.height = size + 'px';
    var left = rect.left + rect.width / 2 - size / 2;
    var top = rect.top + rect.height / 2 - size / 2;
    left = clamp(left, 8, window.innerWidth - size - 8);
    top = clamp(top, 64, window.innerHeight - size - 8);
    gizmo.style.left = Math.round(left) + 'px';
    gizmo.style.top = Math.round(top) + 'px';
    if (gizmoCenter) {
      if (activeType === 'mag-plate') {
        gizmoCenter.textContent = Math.round(activeY) + 'px';
      } else {
        gizmoCenter.textContent = Math.round(activeRotate) + '° · ×' + activeScale.toFixed(2);
      }
    }
  }

  function positionDock() {
    if (!canvasMode || !activeEl || dock.hidden) {
      return;
    }
    var rect = activeEl.getBoundingClientRect();
    var dockW = dock.offsetWidth || 280;
    var dockH = dock.offsetHeight || 120;
    var left = rect.right + 14;
    var top = rect.top;
    if (left + dockW > window.innerWidth - 12) {
      left = rect.left - dockW - 14;
    }
    if (left < 12) {
      left = clamp(rect.left + rect.width / 2 - dockW / 2, 12, window.innerWidth - dockW - 12);
      top = rect.bottom + 14;
      if (top + dockH > window.innerHeight - 12) {
        top = Math.max(72, rect.top - dockH - 14);
      }
    }
    top = clamp(top, 72, window.innerHeight - dockH - 12);
    dock.style.left = Math.round(left) + 'px';
    dock.style.top = Math.round(top) + 'px';
    positionGizmo();
  }

  function stopHold() {
    if (holdTimer) {
      clearTimeout(holdTimer);
      holdTimer = null;
    }
    if (holdInterval) {
      clearInterval(holdInterval);
      holdInterval = null;
    }
  }

  function hideGizmo() {
    stopHold();
    gizmo.hidden = true;
    gizmo.classList.remove('ve-gizmo--plate', 've-gizmo--full');
  }

  function showGizmo() {
    var canMove = activeType === 'mag-image' || activeType === 'mag-layout' || activeType === 'mag-plate';
    if (!canMove) {
      hideGizmo();
      return;
    }
    gizmo.hidden = false;
    gizmo.classList.toggle('ve-gizmo--plate', activeType === 'mag-plate');
    gizmo.classList.toggle('ve-gizmo--full', activeType === 'mag-image' || activeType === 'mag-layout');
    positionGizmo();
  }

  function hideDock() {
    dock.hidden = true;
    hideGizmo();
    setDockError('');
    if (dockTextarea) {
      dockTextarea.hidden = true;
      dockTextarea.value = '';
    }
    if (dockFile) {
      dockFile.value = '';
    }
  }

  function showDock(label) {
    canvasMode = true;
    panel.hidden = true;
    dock.hidden = false;
    dockTitle.textContent = label || 'Правка на месте';
    setDockError('');

    dockTools.hidden = activeType !== 'mag-image';
    dockFileLabel.hidden = activeType !== 'mag-image';
    dockDelete.hidden = !(activeType === 'mag-image' && activeEl && activeEl.getAttribute('data-ve-has-image') === '1');

    if (activeType === 'mag-plate' || activeField === 'home_lead_badge') {
      dockHint.textContent =
        activeType === 'mag-plate'
          ? 'Дуга и стрелки — сдвиг плашки; текст ниже'
          : 'Текст бейджа';
      dockTextarea.hidden = false;
      dockTextarea.value = readCurrentValue(activeEl, activeType === 'mag-plate' ? 'mag-plate' : 'textarea');
    } else if (activeType === 'mag-layout') {
      dockHint.textContent = 'Дуга со стрелками и +/− · можно перетащить журнал';
      dockTextarea.hidden = true;
    } else if (activeType === 'mag-image') {
      dockHint.textContent = 'Дуга со стрелками и +/− · Файл — замена изображения';
      dockTextarea.hidden = true;
    } else {
      dockHint.textContent = 'Правка на месте';
      dockTextarea.hidden = true;
    }

    updateMagMeta();
    showGizmo();
    positionDock();
  }

  function closePanel() {
    panel.hidden = true;
    hideDock();
    if (activeEl) {
      activeEl.classList.remove('is-ve-active');
      activeEl.classList.remove('is-ve-dragging');
    }
    activeEl = null;
    activeField = '';
    activeType = 'text';
    activeDataset = '';
    activeItemId = '';
    activeRotate = 0;
    activeScale = 1;
    activeX = 0;
    activeY = 0;
    canvasMode = false;
    dragState = null;
    if (toolsEl) {
      toolsEl.hidden = true;
    }
    if (metaEl) {
      metaEl.hidden = true;
      metaEl.textContent = '';
    }
    setError('');
  }

  function parseNumAttr(el, name, fallback) {
    var n = parseFloat(el.getAttribute(name) || String(fallback));
    return Number.isNaN(n) ? fallback : n;
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
      activeEl.classList.remove('is-ve-dragging');
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

    if (isCanvasType(type, field)) {
      activeRotate = Math.round(parseNumAttr(el, 'data-ve-rotate', type === 'mag-layout' ? 6 : 0));
      activeScale = parseNumAttr(el, 'data-ve-scale', 1);
      if (activeScale <= 0) {
        activeScale = 1;
      }
      activeX = parseNumAttr(el, 'data-ve-x', 0);
      activeY = parseNumAttr(el, 'data-ve-y', type === 'mag-plate' ? 18 : 0);
      applyCanvasTransform();
      showDock(label);
      return;
    }

    canvasMode = false;
    hideDock();

    if (type === 'image') {
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

  function pickVeTarget(target) {
    if (target.closest('[data-ve-select-layout]')) {
      return document.querySelector('.mortgage-quiz__publication--magazine[data-ve-field="magazine_layout"]');
    }
    var nested = target.closest('[data-ve-field]');
    if (!nested) {
      return null;
    }
    // Предпочитаем фото/логотип внутри журнала, а не сам журнал
    if (
      nested.getAttribute('data-ve-type') === 'mag-layout' &&
      target.closest('.mortgage-quiz__mag-photo[data-ve-field], .mortgage-quiz__mag-logo[data-ve-field]')
    ) {
      return target.closest('.mortgage-quiz__mag-photo[data-ve-field], .mortgage-quiz__mag-logo[data-ve-field]');
    }
    return nested;
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
      if (panel.contains(target) || dock.contains(target) || gizmo.contains(target)) {
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

      var veEl = pickVeTarget(target);
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

      event.preventDefault();
      event.stopPropagation();
      location.assign(withVe(a.href));
    },
    true
  );

  document.addEventListener('pointerdown', function (event) {
    if (!canvasMode || !activeEl || event.button !== 0) {
      return;
    }
    var target = event.target;
    if (!(target instanceof Element)) {
      return;
    }
    if (dock.contains(target) || panel.contains(target) || bar.contains(target) || gizmo.contains(target)) {
      return;
    }
    if (!activeEl.contains(target) && target !== activeEl) {
      return;
    }
    if (activeType !== 'mag-image' && activeType !== 'mag-layout' && activeType !== 'mag-plate') {
      return;
    }

    var parent = activeEl.closest('.mortgage-quiz__visual') || activeEl.parentElement;
    var bounds = parent ? parent.getBoundingClientRect() : { width: 400, height: 400 };
    dragState = {
      pointerId: event.pointerId,
      startX: event.clientX,
      startY: event.clientY,
      origX: activeX,
      origY: activeY,
      unitX: Math.max(bounds.width, 1) / 100,
      unitY: Math.max(bounds.height, 1) / (activeType === 'mag-plate' ? 1 : 100),
    };
    activeEl.classList.add('is-ve-dragging');
    try {
      activeEl.setPointerCapture(event.pointerId);
    } catch (err) {
      // ignore
    }
    event.preventDefault();
  });

  document.addEventListener('pointermove', function (event) {
    if (!dragState || !activeEl) {
      return;
    }
    var dx = event.clientX - dragState.startX;
    var dy = event.clientY - dragState.startY;
    if (activeType === 'mag-plate') {
      activeY = clamp(dragState.origY - dy, 0, 120);
    } else {
      activeX = clamp(round1(dragState.origX + dx / dragState.unitX), -50, 50);
      activeY = clamp(round1(dragState.origY + dy / dragState.unitY), -50, 50);
      if (activeType === 'mag-layout') {
        activeX = clamp(activeX, -40, 40);
        activeY = clamp(activeY, -40, 40);
      }
    }
    applyCanvasTransform();
  });

  function endDrag(event) {
    if (!dragState) {
      return;
    }
    if (event && dragState.pointerId !== event.pointerId) {
      return;
    }
    if (activeEl) {
      activeEl.classList.remove('is-ve-dragging');
      try {
        if (event) {
          activeEl.releasePointerCapture(event.pointerId);
        }
      } catch (err) {
        // ignore
      }
    }
    dragState = null;
  }

  document.addEventListener('pointerup', endDrag);
  document.addEventListener('pointercancel', endDrag);
  window.addEventListener('resize', positionDock);
  window.addEventListener('scroll', positionDock, true);

  if (reloadBtn) {
    reloadBtn.addEventListener('click', function () {
      location.reload();
    });
  }

  cancelBtn.addEventListener('click', closePanel);
  dockClose.addEventListener('click', closePanel);

  document.addEventListener('keydown', function (event) {
    if (!canvasMode || !activeEl) {
      return;
    }
    if (event.target && (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA')) {
      return;
    }
    var key = event.key;
    if (key === 'ArrowUp') {
      event.preventDefault();
      nudgeCanvas('move-up');
    } else if (key === 'ArrowDown') {
      event.preventDefault();
      nudgeCanvas('move-down');
    } else if (key === 'ArrowLeft') {
      event.preventDefault();
      if (event.shiftKey) {
        nudgeCanvas('rotate-left');
      } else {
        nudgeCanvas('move-left');
      }
    } else if (key === 'ArrowRight') {
      event.preventDefault();
      if (event.shiftKey) {
        nudgeCanvas('rotate-right');
      } else {
        nudgeCanvas('move-right');
      }
    } else if (key === '+' || key === '=') {
      event.preventDefault();
      nudgeCanvas('zoom-in');
    } else if (key === '-' || key === '_') {
      event.preventDefault();
      nudgeCanvas('zoom-out');
    } else if (key === 'Escape') {
      closePanel();
    }
  });

  function nudgeCanvas(action) {
    if (!activeEl) {
      return;
    }
    var moveStep = activeType === 'mag-plate' ? 2 : 1.5;
    var canTransform = activeType === 'mag-image' || activeType === 'mag-layout';
    var canMove =
      activeType === 'mag-image' || activeType === 'mag-layout' || activeType === 'mag-plate';

    if (!canMove && !canTransform) {
      return;
    }

    if (action === 'move-up' && canMove) {
      if (activeType === 'mag-plate') {
        activeY = clamp(activeY + moveStep, 0, 120);
      } else {
        activeY = clamp(round1(activeY - moveStep), activeType === 'mag-layout' ? -40 : -50, activeType === 'mag-layout' ? 40 : 50);
      }
    } else if (action === 'move-down' && canMove) {
      if (activeType === 'mag-plate') {
        activeY = clamp(activeY - moveStep, 0, 120);
      } else {
        activeY = clamp(round1(activeY + moveStep), activeType === 'mag-layout' ? -40 : -50, activeType === 'mag-layout' ? 40 : 50);
      }
    } else if (action === 'move-left' && canMove && activeType !== 'mag-plate') {
      activeX = clamp(round1(activeX - moveStep), activeType === 'mag-layout' ? -40 : -50, activeType === 'mag-layout' ? 40 : 50);
    } else if (action === 'move-right' && canMove && activeType !== 'mag-plate') {
      activeX = clamp(round1(activeX + moveStep), activeType === 'mag-layout' ? -40 : -50, activeType === 'mag-layout' ? 40 : 50);
    } else if (action === 'rotate-left' && canTransform) {
      activeRotate = clamp(activeRotate - 5, -180, 180);
    } else if (action === 'rotate-right' && canTransform) {
      activeRotate = clamp(activeRotate + 5, -180, 180);
    } else if (action === 'zoom-out' && canTransform) {
      activeScale = clamp(
        round2(activeScale - 0.05),
        activeType === 'mag-layout' ? 0.6 : 0.5,
        activeType === 'mag-layout' ? 1.5 : 2.5
      );
    } else if (action === 'zoom-in' && canTransform) {
      activeScale = clamp(
        round2(activeScale + 0.05),
        activeType === 'mag-layout' ? 0.6 : 0.5,
        activeType === 'mag-layout' ? 1.5 : 2.5
      );
    } else {
      return;
    }
    applyCanvasTransform();
  }

  function startHold(action) {
    stopHold();
    nudgeCanvas(action);
    holdTimer = setTimeout(function () {
      holdInterval = setInterval(function () {
        nudgeCanvas(action);
      }, 55);
    }, 320);
  }

  gizmo.addEventListener('pointerdown', function (event) {
    var btn = event.target.closest('[data-ve-gizmo]');
    if (!btn) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    startHold(btn.getAttribute('data-ve-gizmo') || '');
  });

  gizmo.addEventListener('pointerup', stopHold);
  gizmo.addEventListener('pointerleave', stopHold);
  gizmo.addEventListener('pointercancel', stopHold);

  // клик по кнопкам дуги не должен всплывать к выбору полей
  gizmo.addEventListener('click', function (event) {
    event.preventDefault();
    event.stopPropagation();
  });

  function bindMagTool(btn, handler) {
    if (!btn) {
      return;
    }
    btn.addEventListener('click', function () {
      if (!activeEl) {
        return;
      }
      handler();
      applyCanvasTransform();
    });
  }

  bindMagTool(rotateLeftBtn, function () {
    activeRotate = clamp(activeRotate - 5, -180, 180);
  });
  bindMagTool(rotateRightBtn, function () {
    activeRotate = clamp(activeRotate + 5, -180, 180);
  });
  bindMagTool(zoomOutBtn, function () {
    activeScale = clamp(round2(activeScale - 0.05), 0.5, 2.5);
  });
  bindMagTool(zoomInBtn, function () {
    activeScale = clamp(round2(activeScale + 0.05), 0.5, 2.5);
  });

  function deleteActiveImage() {
    var deleteDataset = activeDataset;
    var deleteId = activeItemId;
    if (activeType === 'mag-image') {
      deleteDataset = 'magazine';
      deleteId = magFieldToKind(activeField);
    } else if (!deleteDataset || !deleteId) {
      return;
    }
    setError('');
    setDockError('');
    setStatus('Удаление…');
    if (deleteImageBtn) {
      deleteImageBtn.disabled = true;
    }
    if (dockDelete) {
      dockDelete.disabled = true;
    }
    saveBtn.disabled = true;
    dockSave.disabled = true;
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
        setDockError(err.message || 'Ошибка');
        setStatus('');
      })
      .finally(function () {
        if (deleteImageBtn) {
          deleteImageBtn.disabled = false;
        }
        if (dockDelete) {
          dockDelete.disabled = false;
        }
        saveBtn.disabled = false;
        dockSave.disabled = false;
      });
  }

  if (deleteImageBtn) {
    deleteImageBtn.addEventListener('click', deleteActiveImage);
  }
  if (dockDelete) {
    dockDelete.addEventListener('click', deleteActiveImage);
  }

  function uploadMagazineFile(file) {
    var magFd = new FormData();
    magFd.append('csrf', boot.csrf || '');
    magFd.append('action', 'upload_image');
    magFd.append('dataset', 'magazine');
    magFd.append('id', magFieldToKind(activeField));
    magFd.append('file', file);
    return fetch(boot.saveUrl || '/admin/api/visual-save.php', {
      method: 'POST',
      body: magFd,
      credentials: 'same-origin',
    }).then(function (r) {
      return r.json().then(function (data) {
        return { ok: r.ok, data: data };
      });
    }).then(function (res) {
      if (!res.ok || !res.data || !res.data.ok) {
        throw new Error((res.data && res.data.error) || 'Ошибка загрузки');
      }
    });
  }

  function saveCanvasSelection(fileInput) {
    if (!activeField) {
      return;
    }
    setDockError('');
    setStatus('Сохранение…');
    dockSave.disabled = true;
    saveBtn.disabled = true;

    var file = fileInput && fileInput.files && fileInput.files[0];
    var uploadPromise = Promise.resolve();
    if (activeType === 'mag-image' && file) {
      uploadPromise = uploadMagazineFile(file);
    }

    var pairs = [];
    if (activeType === 'mag-image') {
      pairs = [
        [magFieldToRotateKey(activeField), String(activeRotate)],
        [magFieldToScaleKey(activeField), String(activeScale)],
        [magFieldToXKey(activeField), String(activeX)],
        [magFieldToYKey(activeField), String(activeY)],
      ];
    } else if (activeType === 'mag-layout') {
      pairs = [
        ['magazine_layout_rotate', String(activeRotate)],
        ['magazine_layout_scale', String(activeScale)],
        ['magazine_layout_x', String(activeX)],
        ['magazine_layout_y', String(activeY)],
      ];
    } else if (activeType === 'mag-plate') {
      var plateText = (dockTextarea.value || '').trim();
      pairs = [
        ['home_lead_calc_label', plateText],
        ['home_lead_calc_offset', String(Math.round(activeY))],
      ];
    } else if (activeField === 'home_lead_badge') {
      pairs = [['home_lead_badge', dockTextarea.value || '']];
    }

    uploadPromise
      .then(function () {
        return saveFieldsSequential(pairs);
      })
      .then(function () {
        setStatus('Сохранено');
        window.location.reload();
      })
      .catch(function (err) {
        setDockError(err.message || 'Ошибка');
        setStatus('');
      })
      .finally(function () {
        dockSave.disabled = false;
        saveBtn.disabled = false;
      });
  }

  dockSave.addEventListener('click', function () {
    saveCanvasSelection(dockFile);
  });

  saveBtn.addEventListener('click', function () {
    if (!activeField) {
      return;
    }

    if (canvasMode) {
      saveCanvasSelection(dockFile);
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
