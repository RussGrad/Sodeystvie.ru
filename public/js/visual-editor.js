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

  function angleDeg(cx, cy, x, y) {
    return (Math.atan2(y - cy, x - cx) * 180) / Math.PI;
  }

  function dist(ax, ay, bx, by) {
    var dx = bx - ax;
    var dy = by - ay;
    return Math.sqrt(dx * dx + dy * dy);
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
    '<p class="ve-bar__hint">Клик — выделить объект. Тяните за угол (размер), за кружок сверху (поворот), за сам объект (сдвиг). Внизу — сохранить.</p>' +
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
    '<div class="ve-panel__actions">' +
    '<button type="button" class="ve-bar__btn ve-bar__btn--primary" id="ve-panel-save">Сохранить</button>' +
    '<button type="button" class="ve-bar__btn" id="ve-panel-delete-image" hidden>Удалить изображение</button>' +
    '<button type="button" class="ve-bar__btn" id="ve-panel-cancel">Отмена</button>' +
    '</div>';
  document.body.appendChild(panel);

  var frame = document.createElement('div');
  frame.className = 've-frame';
  frame.hidden = true;
  frame.innerHTML =
    '<div class="ve-frame__box" id="ve-frame-box">' +
    '<button type="button" class="ve-frame__handle ve-frame__handle--nw" data-ve-handle="scale" data-corner="nw" title="Масштаб"></button>' +
    '<button type="button" class="ve-frame__handle ve-frame__handle--ne" data-ve-handle="scale" data-corner="ne" title="Масштаб"></button>' +
    '<button type="button" class="ve-frame__handle ve-frame__handle--sw" data-ve-handle="scale" data-corner="sw" title="Масштаб"></button>' +
    '<button type="button" class="ve-frame__handle ve-frame__handle--se" data-ve-handle="scale" data-corner="se" title="Масштаб"></button>' +
    '<span class="ve-frame__rot-line" aria-hidden="true"></span>' +
    '<button type="button" class="ve-frame__handle ve-frame__handle--rotate" data-ve-handle="rotate" title="Повернуть"></button>' +
    '</div>';
  document.body.appendChild(frame);

  var actions = document.createElement('div');
  actions.className = 've-actions';
  actions.hidden = true;
  actions.innerHTML =
    '<div class="ve-actions__info">' +
    '<strong class="ve-actions__title" id="ve-actions-title">Объект</strong>' +
    '<span class="ve-actions__meta" id="ve-actions-meta"></span>' +
    '</div>' +
    '<textarea class="ve-actions__textarea" id="ve-actions-textarea" hidden rows="2" placeholder="Текст"></textarea>' +
    '<p class="ve-actions__error" id="ve-actions-error" hidden></p>' +
    '<div class="ve-actions__btns">' +
    '<button type="button" class="ve-bar__btn ve-bar__btn--primary" id="ve-actions-save">Сохранить</button>' +
    '<label class="ve-bar__btn ve-actions__file" id="ve-actions-file-label" hidden>' +
    'Сменить изображение' +
    '<input type="file" id="ve-actions-file" accept="image/png,image/jpeg,image/webp,image/svg+xml" hidden>' +
    '</label>' +
    '<button type="button" class="ve-bar__btn" id="ve-actions-delete" hidden>Удалить</button>' +
    '<button type="button" class="ve-bar__btn" id="ve-actions-cancel">Отмена</button>' +
    '</div>';
  document.body.appendChild(actions);

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

  var frameBox = document.getElementById('ve-frame-box');
  var actionsTitle = document.getElementById('ve-actions-title');
  var actionsMeta = document.getElementById('ve-actions-meta');
  var actionsTextarea = document.getElementById('ve-actions-textarea');
  var actionsError = document.getElementById('ve-actions-error');
  var actionsSave = document.getElementById('ve-actions-save');
  var actionsDelete = document.getElementById('ve-actions-delete');
  var actionsCancel = document.getElementById('ve-actions-cancel');
  var actionsFile = document.getElementById('ve-actions-file');
  var actionsFileLabel = document.getElementById('ve-actions-file-label');

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

  function isCanvasType(type, field) {
    return (
      type === 'mag-image' ||
      type === 'mag-layout' ||
      type === 'mag-plate' ||
      field === 'home_lead_badge'
    );
  }

  function canFreeTransform() {
    return activeType === 'mag-image' || activeType === 'mag-layout';
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

  function setActionsError(msg) {
    if (!actionsError) {
      return;
    }
    if (!msg) {
      actionsError.hidden = true;
      actionsError.textContent = '';
      return;
    }
    actionsError.hidden = false;
    actionsError.textContent = msg;
  }

  function updateMeta() {
    var text = '';
    if (activeType === 'mag-plate') {
      text = 'положение: ' + Math.round(activeY) + 'px';
    } else if (canFreeTransform()) {
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
    if (actionsMeta) {
      actionsMeta.textContent = text;
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
    updateMeta();
    positionFrame();
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

  function positionFrame() {
    if (!canvasMode || !activeEl || frame.hidden) {
      return;
    }
    var rect = activeEl.getBoundingClientRect();
    var cx = rect.left + rect.width / 2;
    var cy = rect.top + rect.height / 2;
    var w;
    var h;
    if (canFreeTransform()) {
      w = Math.max(48, activeEl.offsetWidth * activeScale);
      h = Math.max(48, activeEl.offsetHeight * activeScale);
      frame.style.transform = 'rotate(' + activeRotate + 'deg)';
    } else {
      w = Math.max(48, rect.width);
      h = Math.max(24, rect.height);
      frame.style.transform = '';
    }
    frame.style.width = Math.round(w) + 'px';
    frame.style.height = Math.round(h) + 'px';
    frame.style.left = Math.round(cx - w / 2) + 'px';
    frame.style.top = Math.round(cy - h / 2) + 'px';
  }

  function hideFrame() {
    frame.hidden = true;
    frame.classList.remove('ve-frame--plate', 've-frame--transform');
  }

  function showFrame() {
    var transformable = canFreeTransform();
    var movable = transformable || activeType === 'mag-plate';
    if (!movable) {
      hideFrame();
      return;
    }
    frame.hidden = false;
    frame.classList.toggle('ve-frame--plate', activeType === 'mag-plate');
    frame.classList.toggle('ve-frame--transform', transformable);
    positionFrame();
  }

  function hideActions() {
    actions.hidden = true;
    setActionsError('');
    if (actionsTextarea) {
      actionsTextarea.hidden = true;
      actionsTextarea.value = '';
    }
    if (actionsFile) {
      actionsFile.value = '';
    }
  }

  function showActions(label) {
    canvasMode = true;
    panel.hidden = true;
    actions.hidden = false;
    document.body.classList.add('ve-canvas-editing');
    actionsTitle.textContent = label || 'Объект';
    setActionsError('');

    var isImage = activeType === 'mag-image';
    actionsFileLabel.hidden = !isImage;
    actionsDelete.hidden = !(isImage && activeEl && activeEl.getAttribute('data-ve-has-image') === '1');

    if (activeType === 'mag-plate' || activeField === 'home_lead_badge') {
      actionsTextarea.hidden = false;
      actionsTextarea.value = readCurrentValue(
        activeEl,
        activeType === 'mag-plate' ? 'mag-plate' : 'textarea'
      );
    } else {
      actionsTextarea.hidden = true;
    }

    updateMeta();
    showFrame();
  }

  function closePanel() {
    panel.hidden = true;
    hideFrame();
    hideActions();
    document.body.classList.remove('ve-canvas-editing');
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
      showActions(label);
      return;
    }

    canvasMode = false;
    hideFrame();
    hideActions();
    document.body.classList.remove('ve-canvas-editing');

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
    if (
      nested.getAttribute('data-ve-type') === 'mag-layout' &&
      target.closest('.mortgage-quiz__mag-photo[data-ve-field], .mortgage-quiz__mag-logo[data-ve-field]')
    ) {
      return target.closest('.mortgage-quiz__mag-photo[data-ve-field], .mortgage-quiz__mag-logo[data-ve-field]');
    }
    return nested;
  }

  function getActiveCenter() {
    if (!activeEl) {
      return { x: 0, y: 0 };
    }
    var rect = activeEl.getBoundingClientRect();
    return {
      x: rect.left + rect.width / 2,
      y: rect.top + rect.height / 2,
    };
  }

  function scaleLimits() {
    if (activeType === 'mag-layout') {
      return { min: 0.6, max: 1.5 };
    }
    return { min: 0.5, max: 2.5 };
  }

  function posLimits() {
    if (activeType === 'mag-layout') {
      return { min: -40, max: 40 };
    }
    return { min: -50, max: 50 };
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
      if (panel.contains(target) || frame.contains(target) || actions.contains(target)) {
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

  function beginDrag(event, mode, corner) {
    if (!activeEl) {
      return;
    }
    var parent = activeEl.closest('.mortgage-quiz__visual') || activeEl.parentElement;
    var bounds = parent ? parent.getBoundingClientRect() : { width: 400, height: 400 };
    var center = getActiveCenter();
    dragState = {
      mode: mode,
      corner: corner || '',
      pointerId: event.pointerId,
      startX: event.clientX,
      startY: event.clientY,
      origX: activeX,
      origY: activeY,
      origRotate: activeRotate,
      origScale: activeScale,
      centerX: center.x,
      centerY: center.y,
      startAngle: angleDeg(center.x, center.y, event.clientX, event.clientY),
      startDist: Math.max(1, dist(center.x, center.y, event.clientX, event.clientY)),
      unitX: Math.max(bounds.width, 1) / 100,
      unitY: Math.max(bounds.height, 1) / (activeType === 'mag-plate' ? 1 : 100),
    };
    activeEl.classList.add('is-ve-dragging');
    document.body.classList.add('ve-is-transforming');
    try {
      (event.currentTarget || frame).setPointerCapture(event.pointerId);
    } catch (err) {
      // ignore
    }
    event.preventDefault();
    event.stopPropagation();
  }

  frame.addEventListener('pointerdown', function (event) {
    if (!canvasMode || !activeEl || event.button !== 0) {
      return;
    }
    var target = event.target;
    if (!(target instanceof Element)) {
      return;
    }
    var handle = target.closest('[data-ve-handle]');
    if (handle) {
      var mode = handle.getAttribute('data-ve-handle') || '';
      if (mode === 'rotate' && canFreeTransform()) {
        beginDrag(event, 'rotate');
        return;
      }
      if (mode === 'scale' && canFreeTransform()) {
        beginDrag(event, 'scale', handle.getAttribute('data-corner') || 'se');
        return;
      }
    }
    if (activeType === 'mag-plate' || canFreeTransform()) {
      beginDrag(event, 'move');
    }
  });

  document.addEventListener('pointerdown', function (event) {
    if (!canvasMode || !activeEl || event.button !== 0 || dragState) {
      return;
    }
    var target = event.target;
    if (!(target instanceof Element)) {
      return;
    }
    if (frame.contains(target) || actions.contains(target) || panel.contains(target) || bar.contains(target)) {
      return;
    }
    if (!activeEl.contains(target) && target !== activeEl) {
      return;
    }
    if (activeType !== 'mag-image' && activeType !== 'mag-layout' && activeType !== 'mag-plate') {
      return;
    }
    beginDrag(event, 'move');
  });

  document.addEventListener('pointermove', function (event) {
    if (!dragState || !activeEl) {
      return;
    }

    if (dragState.mode === 'rotate') {
      var ang = angleDeg(dragState.centerX, dragState.centerY, event.clientX, event.clientY);
      var delta = ang - dragState.startAngle;
      activeRotate = clamp(Math.round(dragState.origRotate + delta), -180, 180);
      applyCanvasTransform();
      return;
    }

    if (dragState.mode === 'scale') {
      var d = dist(dragState.centerX, dragState.centerY, event.clientX, event.clientY);
      var ratio = d / dragState.startDist;
      var lim = scaleLimits();
      activeScale = clamp(round2(dragState.origScale * ratio), lim.min, lim.max);
      applyCanvasTransform();
      return;
    }

    // move
    var dx = event.clientX - dragState.startX;
    var dy = event.clientY - dragState.startY;
    if (activeType === 'mag-plate') {
      activeY = clamp(dragState.origY - dy, 0, 120);
    } else {
      var limP = posLimits();
      activeX = clamp(round1(dragState.origX + dx / dragState.unitX), limP.min, limP.max);
      activeY = clamp(round1(dragState.origY + dy / dragState.unitY), limP.min, limP.max);
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
    }
    document.body.classList.remove('ve-is-transforming');
    try {
      if (event) {
        frame.releasePointerCapture(event.pointerId);
      }
    } catch (err) {
      // ignore
    }
    dragState = null;
    positionFrame();
  }

  document.addEventListener('pointerup', endDrag);
  document.addEventListener('pointercancel', endDrag);
  window.addEventListener('resize', positionFrame);
  window.addEventListener('scroll', positionFrame, true);

  if (reloadBtn) {
    reloadBtn.addEventListener('click', function () {
      location.reload();
    });
  }

  cancelBtn.addEventListener('click', closePanel);
  actionsCancel.addEventListener('click', closePanel);

  document.addEventListener('keydown', function (event) {
    if (!canvasMode || !activeEl) {
      return;
    }
    if (event.target && (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA')) {
      return;
    }
    if (event.key === 'Escape') {
      closePanel();
    }
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
    setActionsError('');
    setStatus('Удаление…');
    if (deleteImageBtn) {
      deleteImageBtn.disabled = true;
    }
    actionsDelete.disabled = true;
    saveBtn.disabled = true;
    actionsSave.disabled = true;
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
        setActionsError(err.message || 'Ошибка');
        setStatus('');
      })
      .finally(function () {
        if (deleteImageBtn) {
          deleteImageBtn.disabled = false;
        }
        actionsDelete.disabled = false;
        saveBtn.disabled = false;
        actionsSave.disabled = false;
      });
  }

  if (deleteImageBtn) {
    deleteImageBtn.addEventListener('click', deleteActiveImage);
  }
  actionsDelete.addEventListener('click', deleteActiveImage);

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

  function saveCanvasSelection(fileInput) {
    if (!activeField) {
      return;
    }
    setActionsError('');
    setStatus('Сохранение…');
    actionsSave.disabled = true;
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
      pairs = [
        ['home_lead_calc_label', (actionsTextarea.value || '').trim()],
        ['home_lead_calc_offset', String(Math.round(activeY))],
      ];
    } else if (activeField === 'home_lead_badge') {
      pairs = [['home_lead_badge', actionsTextarea.value || '']];
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
        setActionsError(err.message || 'Ошибка');
        setStatus('');
      })
      .finally(function () {
        actionsSave.disabled = false;
        saveBtn.disabled = false;
      });
  }

  actionsSave.addEventListener('click', function () {
    saveCanvasSelection(actionsFile);
  });

  // смена файла — сразу сохраняем вместе с текущим transform
  actionsFile.addEventListener('change', function () {
    if (actionsFile.files && actionsFile.files[0]) {
      saveCanvasSelection(actionsFile);
    }
  });

  saveBtn.addEventListener('click', function () {
    if (!activeField) {
      return;
    }

    if (canvasMode) {
      saveCanvasSelection(actionsFile);
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
