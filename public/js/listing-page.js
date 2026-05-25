(() => {
  const resolveCache = Object.create(null);

  function resolvePhotoDisplayUrl(rawUrl) {
    const u = String(rawUrl || '').trim();
    if (!u) return Promise.resolve('');
    if (resolveCache[u]) return Promise.resolve(resolveCache[u]);
    if (u.indexOf('/api/image.php') >= 0) {
      resolveCache[u] = u;
      return Promise.resolve(u);
    }
    return fetch('/api/crm-resolve-photo.php?url=' + encodeURIComponent(u), { credentials: 'same-origin' })
      .then((r) => (r.ok ? r.json() : null))
      .then((data) => {
        const resolved = data && typeof data.url === 'string' ? data.url.trim() : '';
        if (resolved) resolveCache[u] = resolved;
        return resolved;
      })
      .catch(() => '');
  }

  function loadLazyGalleryImage(img) {
    if (!(img instanceof HTMLImageElement)) return;
    if (img.src && img.src.indexOf('/api/image.php') >= 0) return;
    const raw = img.getAttribute('data-gallery-lazy-raw') || img.getAttribute('data-gallery-thumb-lazy-raw');
    if (!raw) return;
    resolvePhotoDisplayUrl(raw).then((url) => {
      if (url) img.src = url;
      img.removeAttribute('data-gallery-lazy-raw');
      img.removeAttribute('data-gallery-thumb-lazy-raw');
    });
  }

  document.querySelectorAll('[data-gallery-lazy-raw], [data-gallery-thumb-lazy-raw]').forEach((img) => {
    if (!(img instanceof HTMLImageElement)) return;
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            loadLazyGalleryImage(entry.target);
            io.unobserve(entry.target);
          });
        },
        { rootMargin: '160px' }
      );
      io.observe(img);
    } else {
      loadLazyGalleryImage(img);
    }
  });

  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

  document.querySelectorAll('[data-gallery]').forEach((root) => {
    const stage = root.querySelector('[data-gallery-stage]');
    if (!stage) return;

    const slides = Array.from(root.querySelectorAll('[data-gallery-slide]'));
    const thumbs = Array.from(root.querySelectorAll('[data-gallery-thumb]'));
    const prev = root.querySelector('[data-gallery-prev]');
    const next = root.querySelector('[data-gallery-next]');
    const counter = root.querySelector('[data-gallery-counter]');

    const hasSlides = slides.length > 0;
    let index = 0;

    const preloadSlide = (i) => {
      const slide = slides[i];
      if (!slide) return;
      const lazy = slide.querySelector('[data-gallery-lazy-raw]');
      if (lazy instanceof HTMLImageElement) loadLazyGalleryImage(lazy);
    };

    const scrollThumbIntoView = (i) => {
      const thumb = thumbs[i];
      if (!thumb || !('scrollIntoView' in thumb)) return;
      thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    };

    const setActive = (i) => {
      if (!hasSlides) return;
      index = clamp(i, 0, slides.length - 1);

      slides.forEach((slide, si) => {
        const on = si === index;
        slide.classList.toggle('is-active', on);
        slide.hidden = !on;
      });

      thumbs.forEach((thumb, ti) => {
        const on = ti === index;
        thumb.classList.toggle('is-active', on);
        thumb.setAttribute('aria-selected', on ? 'true' : 'false');
      });

      if (counter) counter.textContent = `${index + 1}/${slides.length}`;

      preloadSlide(index);
      preloadSlide(index + 1);
      preloadSlide(index - 1);
      scrollThumbIntoView(index);
    };

    prev?.addEventListener('click', (e) => {
      e.preventDefault();
      setActive(index - 1);
    });
    next?.addEventListener('click', (e) => {
      e.preventDefault();
      setActive(index + 1);
    });

    thumbs.forEach((thumb) => {
      thumb.addEventListener('click', (e) => {
        e.preventDefault();
        const raw = thumb.getAttribute('data-gallery-thumb');
        const i = raw !== null ? parseInt(raw, 10) : 0;
        if (!Number.isNaN(i)) setActive(i);
      });
    });

    root.querySelectorAll('[data-gallery-thumb-lazy-raw]').forEach((img) => {
      if (img instanceof HTMLImageElement) loadLazyGalleryImage(img);
    });

    setActive(0);
    if (!hasSlides) {
      prev?.setAttribute('disabled', 'disabled');
      next?.setAttribute('disabled', 'disabled');
    }
  });

  document.querySelectorAll('[data-listing-fav]').forEach((btn) => {
    if (!(btn instanceof HTMLButtonElement)) return;
    const card = btn.closest('[data-listing-card], [data-listing-object]');
    const id = card?.getAttribute('data-listing-id') || '';
    if (!id || !window.SodeystvieFavorites) return;

    const sync = () => {
      const on = window.SodeystvieFavorites.has(id);
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    };

    sync();
    btn.addEventListener('click', () => {
      window.SodeystvieFavorites.toggle(id);
      sync();
    });
  });
})();
