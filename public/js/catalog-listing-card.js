/**
 * Галерея и избранное в карточках каталога.
 */
(function () {
  'use strict';

  var LEGACY_FAV_KEY = 'sodeystvie:catalog-favs';
  var resolveCache = Object.create(null);

  function favApi() {
    return window.SodeystvieFavorites || null;
  }

  function isFavoritesView() {
    try {
      return new URLSearchParams(window.location.search).get('favorites') === '1';
    } catch (e) {
      return false;
    }
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
    var api = favApi();
    if (!api) return;

    function syncUi() {
      var on = api.has(id);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-label', on ? 'Убрать из избранного' : 'Добавить в избранное');
    }

    syncUi();
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var on = api.toggle(id);
      syncUi();
      // Удаляем карточку только после явного клика пользователя
      if (isFavoritesView() && !on) {
        card.remove();
        updateFavoritesCount();
        showFavoritesEmptyIfNeeded();
      }
    });
  }

  function initCardInteractions(root) {
    if (!(root instanceof Element)) return;
    root.querySelectorAll('[data-listing-gallery]').forEach(function (el) {
      if (el instanceof HTMLElement) initGallery(el);
    });
    root.querySelectorAll('[data-listing-fav]').forEach(function (el) {
      if (el instanceof HTMLButtonElement) initFav(el);
    });
  }

  function updateFavoritesCount(explicitCount) {
    var countEl = document.querySelector('.catalog__count');
    if (!countEl) return;
    var n = typeof explicitCount === 'number'
      ? explicitCount
      : document.querySelectorAll('.catalog-list [data-listing-card]').length;
    countEl.textContent = 'В избранном: ' + String(n);
  }

  function showFavoritesEmptyIfNeeded(message) {
    var list = document.getElementById('catalog-list-root');
    var emptyEl = document.querySelector('.catalog-favorites-empty');
    if (!(list instanceof HTMLElement) || !(emptyEl instanceof HTMLElement)) return;
    if (list.querySelector('[data-listing-card]')) {
      emptyEl.hidden = true;
      list.hidden = false;
      return;
    }
    list.hidden = true;
    if (typeof message === 'string' && message !== '') {
      emptyEl.innerHTML = message;
    }
    emptyEl.hidden = false;
    updateFavoritesCount(0);
  }

  function setFavoritesLoading(isLoading) {
    var loadingEl = document.querySelector('.catalog-favorites-loading');
    if (loadingEl instanceof HTMLElement) {
      loadingEl.hidden = !isLoading;
    }
  }

  function waitForFavoritesApi(attempt) {
    var api = favApi();
    if (api) {
      loadFavoritesView();
      return;
    }
    if ((attempt || 0) >= 40) {
      setFavoritesLoading(false);
      showFavoritesEmptyIfNeeded('Не удалось инициализировать избранное. Обновите страницу.');
      return;
    }
    window.setTimeout(function () {
      waitForFavoritesApi((attempt || 0) + 1);
    }, 50);
  }

  function loadFavoritesView() {
    if (!isFavoritesView()) return;
    var api = favApi();
    var list = document.getElementById('catalog-list-root');
    var emptyEl = document.querySelector('.catalog-favorites-empty');
    if (!api || !(list instanceof HTMLElement)) return;

    var ids = api.getIds();
    if (ids.length === 0) {
      setFavoritesLoading(false);
      list.innerHTML = '';
      list.hidden = true;
      if (emptyEl instanceof HTMLElement) {
        emptyEl.innerHTML = 'В избранном пока ничего нет. <a href="/catalog/">Перейти в каталог</a>.';
        emptyEl.hidden = false;
      }
      updateFavoritesCount(0);
      api.refresh();
      return;
    }

    setFavoritesLoading(true);
    if (emptyEl instanceof HTMLElement) emptyEl.hidden = true;

    fetch('/api/catalog-favorites.php?ids=' + encodeURIComponent(ids.join(',')), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
      .then(function (r) {
        return r.text().then(function (text) {
          var data = null;
          try {
            data = text ? JSON.parse(text) : null;
          } catch (e) {
            data = null;
          }
          return { ok: r.ok, data: data };
        });
      })
      .then(function (result) {
        setFavoritesLoading(false);
        var data = result.data;
        if (!result.ok || !data || typeof data.html !== 'string') {
          list.innerHTML = '';
          list.hidden = true;
          if (emptyEl instanceof HTMLElement) {
            emptyEl.textContent = (data && data.error)
              ? String(data.error)
              : 'Не удалось загрузить избранное. Обновите страницу.';
            emptyEl.hidden = false;
          }
          updateFavoritesCount(0);
          return;
        }

        var missing = Array.isArray(data.missing) ? data.missing : [];
        if (missing.length > 0 && typeof api.removeIds === 'function') {
          api.removeIds(missing);
        } else if (typeof api.refresh === 'function') {
          api.refresh();
        }

        list.innerHTML = data.html;
        list.hidden = data.html === '';
        initCardInteractions(list);
        updateFavoritesCount(typeof data.count === 'number' ? data.count : undefined);

        if (data.html === '') {
          var msg = missing.length > 0
            ? 'Сохранённые объекты больше недоступны в каталоге. <a href="/catalog/">Перейти в каталог</a>.'
            : 'В избранном пока ничего нет. <a href="/catalog/">Перейти в каталог</a>.';
          showFavoritesEmptyIfNeeded(msg);
          return;
        }

        showFavoritesEmptyIfNeeded();
      })
      .catch(function () {
        setFavoritesLoading(false);
        list.innerHTML = '';
        list.hidden = true;
        if (emptyEl instanceof HTMLElement) {
          emptyEl.textContent = 'Не удалось загрузить избранное. Обновите страницу.';
          emptyEl.hidden = false;
        }
        updateFavoritesCount(0);
      });
  }

  function boot() {
    initCardInteractions(document);
    if (isFavoritesView()) {
      waitForFavoritesApi(0);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
