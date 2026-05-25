/**
 * Карта офиса на странице «Контакты» (Яндекс.Карты).
 */
(function () {
  'use strict';

  var root = document.getElementById('contacts-map');
  if (!root) return;

  var officeRaw = root.getAttribute('data-office') || '{}';
  var office = {};

  try {
    office = JSON.parse(officeRaw);
  } catch (e) {
    return;
  }

  if (typeof office.lat !== 'number' || typeof office.lng !== 'number') return;

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function init() {
    if (typeof ymaps === 'undefined') return;

    ymaps.ready(function () {
      var map = new ymaps.Map(
        'contacts-map',
        {
          center: [office.lat, office.lng],
          zoom: office.zoom || 16,
          controls: ['zoomControl', 'fullscreenControl'],
        },
        { suppressMapOpenBlock: true }
      );

      var balloon =
        '<div class="contacts-map-balloon">' +
        '<strong>' +
        escapeHtml(office.title || '') +
        '</strong>' +
        '<p>' +
        escapeHtml(office.address || '') +
        '</p>' +
        '</div>';

      var placemark = new ymaps.Placemark(
        [office.lat, office.lng],
        {
          balloonContent: balloon,
          hintContent: office.title || office.address || '',
        },
        { preset: 'islands#dotIcon', iconColor: '#0d3e36' }
      );

      map.geoObjects.add(placemark);
    });
  }

  if (typeof ymaps !== 'undefined') {
    init();
  } else {
    window.addEventListener('load', init);
  }
})();
