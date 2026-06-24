/**
 * Карта расположения объекта на странице каталога (Яндекс.Карты).
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
      (marker.address
        ? '<p>' + escapeHtml(marker.address) + '</p>'
        : '') +
      (marker.price
        ? '<p class="listing-object-map-balloon__price">' + escapeHtml(marker.price) + '</p>'
        : '') +
      '</div>' +
      '</div>'
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

      var placemark = new ymaps.Placemark([marker.lat, marker.lng], props, opts);

      map.geoObjects.add(placemark);
    });
  }

  if (typeof ymaps !== 'undefined') {
    init();
  } else {
    window.addEventListener('load', init);
  }
})();
