/**
 * Карта расположения объекта на странице каталога (Яндекс.Карты).
 * Для ЖК поддерживает POI-метки и фильтры инфраструктуры.
 */
(function () {
  'use strict';

  var root = document.getElementById('listing-object-map');
  if (!root) return;

  var markerRaw = root.getAttribute('data-marker') || '{}';
  var marker = {};
  var pinApi = window.SodeystvieYmapListingPin;

  try {
    marker = JSON.parse(markerRaw);
  } catch (e) {
    return;
  }

  if (typeof marker.lat !== 'number' || typeof marker.lng !== 'number') return;

  var poiList = Array.isArray(marker.poi) ? marker.poi : [];
  var poiPlacemarks = [];
  var activeFilter = 'all';

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function balloonHtml() {
    var photo = marker.photo
      ? '<img class="listing-object-map-balloon__thumb" src="' +
        escapeHtml(marker.photo) +
        '" alt="" width="72" height="54" loading="lazy" decoding="async">'
      : '';
    return (
      '<div class="listing-object-map-balloon">' +
      photo +
      '<div class="listing-object-map-balloon__body">' +
      '<strong>' +
      escapeHtml(marker.title || '') +
      '</strong>' +
      (marker.address ? '<p>' + escapeHtml(marker.address) + '</p>' : '') +
      (marker.price
        ? '<p class="listing-object-map-balloon__price">' + escapeHtml(marker.price) + '</p>'
        : '') +
      '</div>' +
      '</div>'
    );
  }

  function poiBalloonHtml(poi) {
    var distance =
      poi.distanceM != null
        ? '<p class="listing-object-map-balloon__distance">' + escapeHtml(String(poi.distanceM)) + ' м</p>'
        : '';
    return (
      '<div class="listing-object-map-balloon">' +
      '<div class="listing-object-map-balloon__body">' +
      '<strong>' +
      escapeHtml(poi.name || '') +
      '</strong>' +
      distance +
      '</div></div>'
    );
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

      var html = balloonHtml();
      var props =
        pinApi && typeof pinApi.placemarkProperties === 'function'
          ? pinApi.placemarkProperties(marker, html)
          : { balloonContent: html, hintContent: marker.title || marker.address || '' };
      var opts =
        pinApi && typeof pinApi.placemarkOptions === 'function'
          ? pinApi.placemarkOptions()
          : { preset: 'islands#dotIcon', iconColor: '#0d3e36' };

      map.geoObjects.add(new ymaps.Placemark([marker.lat, marker.lng], props, opts));

      poiList.forEach(function (poi) {
        if (!poi || typeof poi.lat !== 'number' || typeof poi.lng !== 'number') return;
        var category = poi.category || 'other';
        var placemark = new ymaps.Placemark(
          [poi.lat, poi.lng],
          {
            balloonContent: poiBalloonHtml(poi),
            hintContent: poi.name || '',
            category: category,
          },
          { preset: 'islands#grayDotIcon' }
        );
        poiPlacemarks.push({ category: category, placemark: placemark });
        map.geoObjects.add(placemark);
      });

      function applyPoiFilter(filter) {
        activeFilter = filter;
        poiPlacemarks.forEach(function (item) {
          var show = filter === 'all' || item.category === filter;
          item.placemark.options.set('visible', show);
        });
      }

      var filtersRoot = document.querySelector('[data-complex-map-filters]');
      if (filtersRoot) {
        filtersRoot.querySelectorAll('[data-poi-filter]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var filter = btn.getAttribute('data-poi-filter') || 'all';
            filtersRoot.querySelectorAll('[data-poi-filter]').forEach(function (b) {
              b.classList.toggle('is-active', b === btn);
            });
            applyPoiFilter(filter);
          });
        });
      }

      if (poiPlacemarks.length > 0) {
        try {
          var bounds = map.geoObjects.getBounds();
          if (bounds) {
            map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 40 });
          }
        } catch (e) {
          /* ignore */
        }
      }
    });
  }

  if (typeof ymaps !== 'undefined') {
    init();
  } else {
    window.addEventListener('load', init);
  }
})();
