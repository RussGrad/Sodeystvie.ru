/**
 * Карта расположения объекта на странице каталога (Яндекс.Карты).
 */
(function () {
  'use strict';

  var root = document.getElementById('listing-object-map');
  if (!root) return;

  var markerRaw = root.getAttribute('data-marker') || '{}';
  var marker = {};

  try {
    marker = JSON.parse(markerRaw);
  } catch (e) {
    return;
  }

  if (typeof marker.lat !== 'number' || typeof marker.lng !== 'number') return;

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
      var zoom = typeof marker.zoom === 'number' ? marker.zoom : 16;
      var map = new ymaps.Map(
        'listing-object-map',
        {
          center: [marker.lat, marker.lng],
          zoom: zoom,
          controls: ['zoomControl', 'fullscreenControl'],
        },
        { suppressMapOpenBlock: true }
      );

      var balloon =
        '<div class="listing-object-map-balloon">' +
        '<strong>' +
        escapeHtml(marker.title || '') +
        '</strong>' +
        (marker.address
          ? '<p>' + escapeHtml(marker.address) + '</p>'
          : '') +
        (marker.price
          ? '<p class="listing-object-map-balloon__price">' + escapeHtml(marker.price) + '</p>'
          : '') +
        '</div>';

      var placemark = new ymaps.Placemark(
        [marker.lat, marker.lng],
        {
          balloonContent: balloon,
          hintContent: marker.title || marker.address || '',
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
