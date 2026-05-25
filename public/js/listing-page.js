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
    return fetch('/api/crm-resolve-photo.php?url=' + encodeURIComponent(u), {
      credentials: 'same-origin',
    })
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
    const raw = img.getAttribute('data-gallery-lazy-raw');
    if (!raw) return;
    resolvePhotoDisplayUrl(raw).then((url) => {
      if (url) img.src = url;
      img.removeAttribute('data-gallery-lazy-raw');
    });
  }

  document.querySelectorAll('[data-gallery-lazy-raw]').forEach((img) => {
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
        { rootMargin: '120px' }
      );
      io.observe(img);
    } else {
      loadLazyGalleryImage(img);
    }
  });

  const galleries = document.querySelectorAll('[data-gallery]');
  if (!galleries.length) return;

  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

  galleries.forEach((root) => {
    const track = root.querySelector('[data-gallery-track]');
    if (!track) return;

    const slides = Array.from(root.querySelectorAll('[data-gallery-slide]'));
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

    const updateCounter = () => {
      if (!counter) return;
      if (!hasSlides) {
        counter.textContent = '';
        return;
      }
      counter.textContent = `${index + 1}/${slides.length}`;
    };

    const scrollToIndex = (i) => {
      if (!hasSlides) return;
      const w = track.clientWidth || 1;
      index = clamp(i, 0, slides.length - 1);
      track.scrollTo({ left: index * w, behavior: 'smooth' });
      preloadSlide(index);
      preloadSlide(index + 1);
      updateCounter();
    };

    const deriveIndexFromScroll = () => {
      if (!hasSlides) return;
      const w = track.clientWidth || 1;
      const i = Math.round(track.scrollLeft / w);
      index = clamp(i, 0, slides.length - 1);
      preloadSlide(index);
      preloadSlide(index + 1);
      updateCounter();
    };

    prev?.addEventListener('click', (e) => {
      e.preventDefault();
      scrollToIndex(index - 1);
    });
    next?.addEventListener('click', (e) => {
      e.preventDefault();
      scrollToIndex(index + 1);
    });

    track.addEventListener('scroll', () => {
      window.requestAnimationFrame(deriveIndexFromScroll);
    });

    window.addEventListener('resize', () => {
      scrollToIndex(index);
    });

    updateCounter();
    preloadSlide(1);
    if (!hasSlides) {
      prev?.setAttribute('disabled', 'disabled');
      next?.setAttribute('disabled', 'disabled');
    }
  });
})();
