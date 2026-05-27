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
    if (!(img instanceof HTMLImageElement)) return Promise.resolve();
    if (img.src && img.src.indexOf('/api/image.php') >= 0) return Promise.resolve();
    const raw = img.getAttribute('data-gallery-lazy-raw') || img.getAttribute('data-gallery-thumb-lazy-raw');
    if (!raw) return Promise.resolve();
    return resolvePhotoDisplayUrl(raw).then((url) => {
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

  function slideImage(slide) {
    if (!slide) return null;
    return slide.querySelector('.listing-gallery__img');
  }

  function imageSrc(img) {
    if (!(img instanceof HTMLImageElement)) return '';
    return img.currentSrc || img.src || '';
  }

  function bindSwipe(el, onLeft, onRight) {
    if (!(el instanceof HTMLElement)) return;
    let startX = 0;
    let startY = 0;
    el.addEventListener(
      'touchstart',
      (e) => {
        const t = e.changedTouches[0];
        startX = t.clientX;
        startY = t.clientY;
      },
      { passive: true }
    );
    el.addEventListener(
      'touchend',
      (e) => {
        const t = e.changedTouches[0];
        const dx = t.clientX - startX;
        const dy = t.clientY - startY;
        if (Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy)) return;
        if (dx < 0) onLeft();
        else onRight();
      },
      { passive: true }
    );
  }

  document.querySelectorAll('[data-gallery]').forEach((root) => {
    const stage = root.querySelector('[data-gallery-stage]');
    if (!stage) return;

    const slides = Array.from(root.querySelectorAll('[data-gallery-slide]'));
    const thumbs = Array.from(root.querySelectorAll('[data-gallery-thumb]'));
    const lbThumbs = Array.from(root.querySelectorAll('[data-gallery-lightbox-thumb]'));
    const prev = root.querySelector('[data-gallery-prev]');
    const next = root.querySelector('[data-gallery-next]');
    const counter = root.querySelector('[data-gallery-counter]');
    const lightbox = root.querySelector('[data-gallery-lightbox]');
    const lightboxImg = root.querySelector('[data-gallery-lightbox-img]');
    const lightboxStage = root.querySelector('[data-gallery-lightbox-stage]');
    const lightboxCounter = root.querySelector('[data-gallery-lightbox-counter]');
    const lightboxHint = root.querySelector('[data-gallery-lightbox-hint]');
    const lightboxPrev = root.querySelector('[data-gallery-lightbox-prev]');
    const lightboxNext = root.querySelector('[data-gallery-lightbox-next]');
    const openHits = Array.from(root.querySelectorAll('[data-gallery-open]'));

    if (lightbox && lightbox.parentElement !== document.body) {
      document.body.appendChild(lightbox);
    }

    const hasSlides = slides.length > 0;
    let index = 0;
    let lightboxOpen = false;
    let lightboxZoomed = false;

    const preloadSlide = (i) => {
      const img = slideImage(slides[i]);
      if (img instanceof HTMLImageElement) loadLazyGalleryImage(img);
    };

    const scrollThumbIntoView = (list, i) => {
      const thumb = list[i];
      if (!thumb || !('scrollIntoView' in thumb)) return;
      thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    };

    const syncThumbStates = () => {
      thumbs.forEach((thumb, ti) => {
        const on = ti === index;
        thumb.classList.toggle('is-active', on);
        thumb.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      lbThumbs.forEach((thumb, ti) => {
        const on = ti === index;
        thumb.classList.toggle('is-active', on);
        thumb.setAttribute('aria-selected', on ? 'true' : 'false');
      });
    };

    const setActive = (i) => {
      if (!hasSlides) return;
      index = clamp(i, 0, slides.length - 1);

      slides.forEach((slide, si) => {
        const on = si === index;
        slide.classList.toggle('is-active', on);
        slide.hidden = !on;
      });

      syncThumbStates();

      if (counter) counter.textContent = `${index + 1} / ${slides.length}`;

      preloadSlide(index);
      preloadSlide(index + 1);
      preloadSlide(index - 1);
      scrollThumbIntoView(thumbs, index);
      scrollThumbIntoView(lbThumbs, index);

      if (lightboxOpen) syncLightboxImage();
    };

    const syncLightboxImage = () => {
      if (!(lightboxImg instanceof HTMLImageElement)) return;
      const img = slideImage(slides[index]);
      if (!(img instanceof HTMLImageElement)) return;

      const applySrc = () => {
        const src = imageSrc(img);
        if (src) lightboxImg.src = src;
        lightboxImg.alt = img.alt || '';
        if (lightboxCounter) lightboxCounter.textContent = `${index + 1} / ${slides.length}`;
      };

      if (img.getAttribute('data-gallery-lazy-raw')) {
        loadLazyGalleryImage(img).then(applySrc);
      } else {
        applySrc();
      }
    };

    const setLightboxZoom = (on) => {
      lightboxZoomed = on;
      if (lightboxStage) lightboxStage.classList.toggle('is-zoomed', on);
      if (lightboxHint) {
        lightboxHint.textContent = on
          ? 'Клик по фото — уменьшить · Esc — закрыть'
          : 'Клик по фото — приблизить · Esc — закрыть';
      }
    };

    const openLightbox = () => {
      if (!lightbox || !(lightboxImg instanceof HTMLImageElement) || !hasSlides) return;
      syncLightboxImage();
      setLightboxZoom(false);
      lightbox.hidden = false;
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.classList.add('has-modal');
      lightboxOpen = true;
    };

    const closeLightbox = () => {
      if (!lightbox) return;
      lightbox.hidden = true;
      lightbox.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('has-modal');
      lightboxOpen = false;
      setLightboxZoom(false);
    };

    const toggleLightboxZoom = () => {
      if (!lightboxOpen) return;
      setLightboxZoom(!lightboxZoomed);
    };

    const goPrev = () => setActive(index - 1);
    const goNext = () => setActive(index + 1);

    prev?.addEventListener('click', (e) => {
      e.preventDefault();
      goPrev();
    });
    next?.addEventListener('click', (e) => {
      e.preventDefault();
      goNext();
    });

    lightboxPrev?.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      goPrev();
    });
    lightboxNext?.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      goNext();
    });

    thumbs.forEach((thumb) => {
      thumb.addEventListener('click', (e) => {
        e.preventDefault();
        const raw = thumb.getAttribute('data-gallery-thumb');
        const i = raw !== null ? parseInt(raw, 10) : 0;
        if (!Number.isNaN(i)) setActive(i);
      });
    });

    lbThumbs.forEach((thumb) => {
      thumb.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const raw = thumb.getAttribute('data-gallery-lightbox-thumb');
        const i = raw !== null ? parseInt(raw, 10) : 0;
        if (!Number.isNaN(i)) setActive(i);
      });
    });

    openHits.forEach((hit) => {
      hit.addEventListener('click', (e) => {
        e.preventDefault();
        openLightbox();
      });
    });

    lightbox?.querySelectorAll('[data-gallery-lightbox-close]').forEach((el) => {
      el.addEventListener('click', (e) => {
        e.preventDefault();
        closeLightbox();
      });
    });

    lightboxImg?.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleLightboxZoom();
    });

    bindSwipe(stage, goNext, goPrev);
    bindSwipe(lightboxStage, goNext, goPrev);

    document.addEventListener('keydown', (e) => {
      if (!lightboxOpen) return;
      if (e.key === 'Escape') {
        e.preventDefault();
        closeLightbox();
        return;
      }
      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        goPrev();
        return;
      }
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        goNext();
      }
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
