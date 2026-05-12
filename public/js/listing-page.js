(() => {
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
      updateCounter();
    };

    const deriveIndexFromScroll = () => {
      if (!hasSlides) return;
      const w = track.clientWidth || 1;
      const i = Math.round(track.scrollLeft / w);
      index = clamp(i, 0, slides.length - 1);
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

    // init
    updateCounter();
    if (!hasSlides) {
      prev?.setAttribute('disabled', 'disabled');
      next?.setAttribute('disabled', 'disabled');
    }
  });
})();

