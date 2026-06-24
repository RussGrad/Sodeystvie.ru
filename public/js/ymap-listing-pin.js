/**
 * Кастомная метка объекта на Яндекс.Карте: фото + треугольный указатель.
 */
(function (global) {
  'use strict';

  var PIN_W = 48;
  var PIN_H = 54;
  var layoutClass = null;

  function getLayoutClass() {
    if (layoutClass) {
      return layoutClass;
    }
    if (typeof ymaps === 'undefined' || !ymaps.templateLayoutFactory) {
      return null;
    }

    var Layout = ymaps.templateLayoutFactory.createClass(
      '<div class="ymap-listing-pin">' +
        '<div class="ymap-listing-pin__frame">' +
        '<div class="ymap-listing-pin__photo"></div>' +
        '</div>' +
        '<div class="ymap-listing-pin__pointer" aria-hidden="true"></div>' +
        '</div>',
      {
        build: function () {
          Layout.superclass.build.call(this);
          var root = this.getParentElement();
          if (!root) {
            return;
          }
          var photoEl = root.querySelector('.ymap-listing-pin__photo');
          var photo = String(this.getData().properties.get('photo') || '');
          if (photoEl) {
            if (photo) {
              photoEl.style.backgroundImage = 'url(' + JSON.stringify(photo) + ')';
              photoEl.classList.remove('ymap-listing-pin__photo--empty');
            } else {
              photoEl.style.backgroundImage = '';
              photoEl.classList.add('ymap-listing-pin__photo--empty');
            }
          }
          var half = PIN_W / 2;
          this.getData().options.set('shape', {
            type: 'Rectangle',
            coordinates: [[-half, -PIN_H], [half, 0]],
          });
        },
      }
    );

    layoutClass = Layout;
    return layoutClass;
  }

  function placemarkOptions() {
    var Layout = getLayoutClass();
    if (!Layout) {
      return { preset: 'islands#circleDotIcon', iconColor: '#0d3e36' };
    }
    var half = PIN_W / 2;
    return {
      iconLayout: Layout,
      iconOffset: [-half, -PIN_H],
      iconShape: {
        type: 'Rectangle',
        coordinates: [[-half, -PIN_H], [half, 0]],
      },
      zIndex: 2,
    };
  }

  function placemarkProperties(marker, balloonHtml) {
    return {
      balloonContent: balloonHtml,
      hintContent: (marker && marker.title) || '',
      photo: (marker && marker.photo) || '',
    };
  }

  global.SodeystvieYmapListingPin = {
    placemarkOptions: placemarkOptions,
    placemarkProperties: placemarkProperties,
  };
})(typeof window !== 'undefined' ? window : this);
