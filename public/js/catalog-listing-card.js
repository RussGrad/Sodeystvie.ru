/**
 * Галерея и избранное в карточках каталога.
 */
(function () {
  var FAV_KEY = 'sodeystvie:catalog-favs';

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

  function initGallery(root) {
    var raw = root.getAttribute('data-photos') || '[]';
    var photos = [];
    try {
      photos = JSON.parse(raw);
    } catch (e) {
      photos = [];
    }
    if (!Array.isArray(photos) || photos.length === 0) return;

    var img = root.querySelector('[data-listing-gallery-img]');
    var countEl = root.querySelector('[data-listing-gallery-count]');
    var prev = root.querySelector('[data-listing-gallery-prev]');
    var next = root.querySelector('[data-listing-gallery-next]');
    if (!(img instanceof HTMLImageElement)) return;

    var index = 0;
    var total = photos.length;

    function render() {
      img.src = photos[index];
      if (countEl) countEl.textContent = String(index + 1) + '/' + String(total);
    }

    if (prev instanceof HTMLButtonElement) {
      prev.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        index = (index - 1 + total) % total;
        render();
      });
    }
    if (next instanceof HTMLButtonElement) {
      next.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        index = (index + 1) % total;
        render();
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
      var label = btn.querySelector('.listing-card__fav-label');
      if (label) label.textContent = on ? 'в избранном' : 'добавить в избранное';
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
