/**
 * Полноэкранная карта каталога (Яндекс.Карты).
 */
(function () {
  'use strict';

  var root = document.getElementById('catalog-map');
  if (!root) return;

  var markersRaw = root.getAttribute('data-markers') || '[]';
  var centerRaw = root.getAttribute('data-center') || '{}';
  var markers = [];
  var center = { lat: 52.2896, lng: 104.2806, zoom: 11 };

  try {
    markers = JSON.parse(markersRaw);
  } catch (e) {
    markers = [];
  }
  try {
    center = JSON.parse(centerRaw);
  } catch (e2) {
    /* keep default */
  }

  if (!markers.length) return;

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
        'catalog-map',
        {
          center: [center.lat, center.lng],
          zoom: center.zoom || 11,
          controls: ['zoomControl', 'geolocationControl', 'fullscreenControl'],
        },
        { suppressMapOpenBlock: true }
      );

      var collection = new ymaps.GeoObjectCollection();
      var bounds = [];

      markers.forEach(function (m) {
        if (!m || typeof m.lat !== 'number' || typeof m.lng !== 'number') return;
        var coords = [m.lat, m.lng];
        bounds.push(coords);

        var balloon =
          '<div class="catalog-map-balloon">' +
          '<strong>' +
          escapeHtml(m.title || 'Объект') +
          '</strong>' +
          '<p>' +
          escapeHtml(m.price || '') +
          '</p>' +
          '<a href="' +
          escapeHtml(m.href || '#') +
          '">Подробнее</a>' +
          '</div>';

        var placemark = new ymaps.Placemark(
          coords,
          { balloonContent: balloon, hintContent: m.title || '' },
          { preset: 'islands#circleDotIcon', iconColor: '#0d3e36' }
        );
        collection.add(placemark);
      });

      map.geoObjects.add(collection);

      if (bounds.length === 1) {
        map.setCenter(bounds[0], 14, { duration: 200 });
      } else if (bounds.length > 1) {
        map.setBounds(collection.getBounds(), {
          checkZoomRange: true,
          zoomMargin: 40,
          duration: 200,
        });
      }
    });
  }

  if (typeof ymaps !== 'undefined') {
    init();
  } else {
    window.addEventListener('load', init);
  }
})();
