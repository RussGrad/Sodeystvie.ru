(function () {
  const root = document.querySelector('[data-developer-offers]');
  if (!root) return;

  const tabs = root.querySelectorAll('[data-building-filter]');
  const rows = root.querySelectorAll('.developer-offers__row');

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const filter = tab.getAttribute('data-building-filter') || 'all';
      tabs.forEach((t) => {
        const active = t === tab;
        t.classList.toggle('is-active', active);
        t.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      rows.forEach((row) => {
        if (!(row instanceof HTMLElement)) return;
        const buildingId = row.getAttribute('data-building-id') || '';
        const show = filter === 'all' || buildingId === filter;
        row.hidden = !show;
      });
    });
  });
})();
