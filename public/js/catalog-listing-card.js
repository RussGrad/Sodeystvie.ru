/**
 * Галерея и избранное в карточках каталога.
 */
(function () {
  var FAV_KEY = 'sodeystvie:catalog-favs';
  var resolveCache = Object.create(null);

  function readFavs() {
    try {
      var raw = localStorage.getItem(FAV_KEY);
      var parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  function writeFavs(list) {
    try {
      localStorage.setItem(FAV_KEY, JSON.stringify(list));
    } catch (e) {
      /* ignore */
    }
  }

  function isFav(id) {
    return readFavs().indexOf(id) >= 0;
  }

  function toggleFav(id) {
    var list = readFavs();
    var idx = list.indexOf(id);
    if (idx >= 0) list.splice(idx, 1);
    else list.push(id);
    writeFavs(list);
    return idx < 0;
  }

  function parseB64Json(attr) {
    if (!attr) return [];
    try {
      var parsed = JSON.parse(atob(attr));
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  function resolvePhotoUrl(rawUrl) {
    var u = String(rawUrl || '').trim();
    if (!u) return Promise.resolve('');
    if (resolveCache[u]) return Promise.resolve(resolveCache[u]);
    if (u.indexOf('/api/image.php') >= 0) {
      resolveCache[u] = u;
      return Promise.resolve(u);
    }
    if (/^https?:\/\//i.test(u) && u.indexOf('downloader.disk.yandex.ru') >= 0) {
      return fetch('/api/crm-resolve-photo.php?url=' + encodeURIComponent(u), {
        credentials: 'same-origin',
      })
        .then(function (r) {
          return r.ok ? r.json() : null;
        })
        .then(function (data) {
          var resolved = data && typeof data.url === 'string' ? data.url.trim() : u;
          if (resolved) resolveCache[u] = resolved;
          return resolved;
        })
        .catch(function () {
          return u;
        });
    }
    return fetch('/api/crm-resolve-photo.php?url=' + encodeURIComponent(u), {
      credentials: 'same-origin',
    })
      .then(function (r) {
        return r.ok ? r.json() : null;
      })
      .then(function (data) {
        var resolved = data && typeof data.url === 'string' ? data.url.trim() : '';
        if (resolved) resolveCache[u] = resolved;
        return resolved;
      })
      .catch(function () {
        return '';
      });
  }

  function initGallery(root) {
    var resolved = parseB64Json(root.getAttribute('data-photos-b64'));
    var raw = parseB64Json(root.getAttribute('data-photos-raw-b64'));
    if (raw.length === 0 && resolved.length > 0) {
      raw = resolved.slice();
    }

    var img = root.querySelector('[data-listing-gallery-img], .listing-card__photo');
    var countEl = root.querySelector('[data-listing-gallery-count]');
    var prev = root.querySelector('[data-listing-gallery-prev]');
    var next = root.querySelector('[data-listing-gallery-next]');
    if (!(img instanceof HTMLImageElement)) return;

    var index = 0;
    var total = Math.max(raw.length, resolved.length, 1);
    var displayUrls = resolved.slice();

    function ensureUrlAt(i) {
      if (displayUrls[i]) return Promise.resolve(displayUrls[i]);
      var rawUrl = raw[i];
      if (!rawUrl) return Promise.resolve('');
      return resolvePhotoUrl(rawUrl).then(function (url) {
        if (url) displayUrls[i] = url;
        return url;
      });
    }

    function render() {
      var url = displayUrls[index] || '';
      if (url) img.src = url;
      if (countEl) countEl.textContent = String(index + 1) + '/' + String(total);
    }

    render();

    function go(delta) {
      if (total <= 1) return;
      index = (index + delta + total) % total;
      ensureUrlAt(index).then(render);
    }

    if (prev instanceof HTMLButtonElement) {
      prev.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        go(-1);
      });
    }
    if (next instanceof HTMLButtonElement) {
      next.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        go(1);
      });
    }
  }

  function initFav(btn) {
    var card = btn.closest('[data-listing-card]');
    if (!(card instanceof HTMLElement)) return;
    var id = card.getAttribute('data-listing-id') || '';
    if (!id) return;

    function sync() {
      var on = isFav(id);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-label', on ? 'Убрать из избранного' : 'Добавить в избранное');
    }

    sync();
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      toggleFav(id);
      sync();
    });
  }

  document.querySelectorAll('[data-listing-gallery]').forEach(function (el) {
    if (el instanceof HTMLElement) initGallery(el);
  });
  document.querySelectorAll('[data-listing-fav]').forEach(function (el) {
    if (el instanceof HTMLButtonElement) initFav(el);
  });
})();
