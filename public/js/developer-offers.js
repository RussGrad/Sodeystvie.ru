(function () {
  const root = document.querySelector('[data-developer-offers]');
  if (!root) return;

  const dataEl = document.getElementById('developer-offers-data');
  const modal = root.querySelector('[data-developer-offer-modal]');
  const panel = modal?.querySelector('.developer-offer-modal__panel');
  const titleEl = modal?.querySelector('[data-offer-title]');
  const priceEl = modal?.querySelector('[data-offer-price]');
  const priceM2El = modal?.querySelector('[data-offer-price-m2]');
  const specsEl = modal?.querySelector('[data-offer-specs]');
  const locationEl = modal?.querySelector('[data-offer-location]');
  const planImg = modal?.querySelector('[data-offer-plan]');
  const planPlaceholder = modal?.querySelector('[data-offer-plan-placeholder]');

  /** @type {{ complexTitle?: string, city?: string, district?: string, address?: string, offers?: Array<Record<string, unknown>> }} */
  let payload = { offers: [] };
  if (dataEl?.textContent) {
    try {
      payload = JSON.parse(dataEl.textContent);
    } catch {
      payload = { offers: [] };
    }
  }

  const offersById = new Map();
  for (const offer of payload.offers || []) {
    const id = typeof offer.layoutId === 'string' ? offer.layoutId : '';
    if (id) offersById.set(id, offer);
  }

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

  function formatRub(value) {
    if (value == null || !Number.isFinite(Number(value))) return '—';
    return new Intl.NumberFormat('ru-RU').format(Math.round(Number(value))) + ' ₽';
  }

  function flatsLabel(count) {
    const n = Number(count) || 0;
    const mod10 = n % 10;
    const mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) return `${n} предложение`;
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return `${n} предложения`;
    return `${n} предложений`;
  }

  /** @param {Record<string, unknown>} offer */
  function renderSpecs(offer) {
    if (!(specsEl instanceof HTMLElement)) return;
    specsEl.replaceChildren();

    /** @type {Array<[string, string]>} */
    const items = [];
    if (offer.roomLabel) items.push(['Планировка', String(offer.roomLabel)]);
    if (offer.area) items.push(['Площадь', String(offer.area)]);
    if (offer.kitchenArea) items.push(['Кухня', String(offer.kitchenArea)]);
    if (offer.floor) items.push(['Этаж', String(offer.floor)]);
    if (offer.buildingName) items.push(['Корпус', String(offer.buildingName)]);
    if (offer.completion) items.push(['Сдача', String(offer.completion)]);
    if (offer.flatsCount != null) items.push(['Вариантов', flatsLabel(offer.flatsCount)]);

    const price = offer.price != null ? Number(offer.price) : null;
    const priceMax = offer.priceMax != null ? Number(offer.priceMax) : null;
    if (priceMax != null && price != null && priceMax > price) {
      items.push(['Цена до', formatRub(priceMax)]);
    }

    for (const [label, value] of items) {
      const dt = document.createElement('dt');
      dt.className = 'developer-offer-modal__spec-label';
      dt.textContent = label;
      const dd = document.createElement('dd');
      dd.className = 'developer-offer-modal__spec-value';
      dd.textContent = value;
      specsEl.append(dt, dd);
    }
  }

  /** @param {Record<string, unknown>} offer */
  function openModal(offer) {
    if (!modal || !(panel instanceof HTMLElement)) return;

    if (titleEl) titleEl.textContent = typeof offer.title === 'string' ? offer.title : '';
    if (priceEl) {
      const price = offer.price != null ? Number(offer.price) : null;
      priceEl.textContent = price != null ? formatRub(price) : 'Цена по запросу';
    }
    if (priceM2El) {
      const price = offer.price != null ? Number(offer.price) : null;
      const areaRaw = typeof offer.area === 'string' ? offer.area.replace(/[^\d.,]/g, '').replace(',', '.') : '';
      const area = areaRaw ? Number(areaRaw) : NaN;
      if (price != null && Number.isFinite(area) && area > 0) {
        priceM2El.textContent = formatRub(Math.round(price / area)) + ' за м²';
        priceM2El.hidden = false;
      } else {
        priceM2El.hidden = true;
        priceM2El.textContent = '';
      }
    }

    renderSpecs(offer);

    if (locationEl) {
      const parts = [payload.address, payload.district, payload.city].filter(
        (v) => typeof v === 'string' && v.trim() !== '',
      );
      if (parts.length > 0) {
        locationEl.textContent = parts.join(' · ');
        locationEl.hidden = false;
      } else {
        locationEl.hidden = true;
        locationEl.textContent = '';
      }
    }

    const planUrl = typeof offer.planImageUrl === 'string' ? offer.planImageUrl.trim() : '';
    if (planImg instanceof HTMLImageElement) {
      if (planUrl) {
        planImg.src = planUrl;
        planImg.alt = typeof offer.title === 'string' ? offer.title : 'Планировка';
        planImg.hidden = false;
        if (planPlaceholder instanceof HTMLElement) planPlaceholder.hidden = true;
      } else {
        planImg.removeAttribute('src');
        planImg.hidden = true;
        if (planPlaceholder instanceof HTMLElement) planPlaceholder.hidden = false;
      }
    }

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('has-modal');
    panel.focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('has-modal');
  }

  function resolveLayoutId(el) {
    const withId = el.closest('[data-layout-id]');
    return withId?.getAttribute('data-layout-id') || '';
  }

  function handleOpenClick(e) {
    const target = e.target;
    if (!(target instanceof Element)) return;
    const opener = target.closest('[data-developer-offer-open]');
    if (!opener || !root.contains(opener)) return;
    e.preventDefault();
    const layoutId = resolveLayoutId(opener);
    const offer = offersById.get(layoutId);
    if (offer) openModal(offer);
  }

  root.addEventListener('click', handleOpenClick);
  root.addEventListener('keydown', (e) => {
    const target = e.target;
    if (!(target instanceof Element)) return;
    const row = target.closest('[data-developer-offer-open][role="button"]');
    if (!row || (e.key !== 'Enter' && e.key !== ' ')) return;
    e.preventDefault();
    const layoutId = row.getAttribute('data-layout-id') || '';
    const offer = offersById.get(layoutId);
    if (offer) openModal(offer);
  });

  modal?.querySelectorAll('[data-developer-offer-close]').forEach((el) => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      closeModal();
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal && !modal.hidden) {
      closeModal();
    }
  });

  if (modal && modal.parentElement !== document.body) {
    document.body.appendChild(modal);
  }
})();
