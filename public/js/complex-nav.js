(function () {
  const nav = document.querySelector('[data-complex-nav]');
  if (!nav) return;

  const links = Array.from(nav.querySelectorAll('[data-complex-nav-link]'));
  if (links.length === 0) return;

  const sections = links
    .map((link) => {
      const href = link.getAttribute('href') || '';
      if (!href.startsWith('#')) return null;
      const id = href.slice(1);
      const el = document.getElementById(id);
      return el ? { link, el } : null;
    })
    .filter(Boolean);

  if (sections.length === 0) return;

  const header = document.querySelector('.site-header');
  const navHeight = () => (header instanceof HTMLElement ? header.offsetHeight : 0) + nav.offsetHeight + 8;

  links.forEach((link) => {
    link.addEventListener('click', (e) => {
      const href = link.getAttribute('href') || '';
      if (!href.startsWith('#')) return;
      const target = document.getElementById(href.slice(1));
      if (!target) return;
      e.preventDefault();
      const top = target.getBoundingClientRect().top + window.scrollY - navHeight();
      window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
      history.replaceState(null, '', href);
    });
  });

  let ticking = false;
  const syncActive = () => {
    ticking = false;
    const offset = navHeight() + 24;
    let current = sections[0];
    for (const item of sections) {
      if (!item) continue;
      if (item.el.getBoundingClientRect().top <= offset) {
        current = item;
      }
    }
    links.forEach((link) => {
      const active = current && link === current.link;
      link.classList.toggle('is-active', Boolean(active));
    });
  };

  const onScroll = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(syncActive);
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll, { passive: true });
  syncActive();
})();
