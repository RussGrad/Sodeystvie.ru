(function () {
  'use strict';

  var container = document.querySelector('[data-admin-items]');
  if (!container) return;

  var addBtn = document.querySelector('[data-admin-add-row]');
  if (addBtn) {
    addBtn.addEventListener('click', function () {
      var items = container.querySelectorAll('[data-admin-item]');
      var last = items[items.length - 1];
      if (!last) return;
      var clone = last.cloneNode(true);
      clone.querySelectorAll('input, textarea, select').forEach(function (el) {
        if (el.type === 'checkbox') {
          el.checked = false;
        } else if (el.type === 'file') {
          el.value = '';
        } else if (el.type === 'hidden' && el.value === '0') {
          el.value = '0';
        } else {
          el.value = '';
        }
      });
      var img = clone.querySelector('.admin-item__photo');
      if (img) img.remove();
      container.appendChild(clone);
      reindexItems();
      bindRemoveButtons();
    });
  }

  function bindRemoveButtons() {
    container.querySelectorAll('[data-admin-remove-row]').forEach(function (btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', function () {
        var items = container.querySelectorAll('[data-admin-item]');
        if (items.length <= 1) {
          items[0].querySelectorAll('input, textarea').forEach(function (el) {
            if (el.type !== 'file' && el.type !== 'checkbox' && el.type !== 'hidden') el.value = '';
          });
          return;
        }
        btn.closest('[data-admin-item]').remove();
        reindexItems();
      });
    });
  }

  function reindexItems() {
    container.querySelectorAll('[data-admin-item]').forEach(function (item, index) {
      var legend = item.querySelector('.admin-item__legend');
      if (legend) legend.textContent = 'Запись ' + (index + 1);
      item.querySelectorAll('[name]').forEach(function (el) {
        if (!el.name) return;
        el.name = el.name.replace(/items\[\d+]/, 'items[' + index + ']');
      });
    });
  }

  bindRemoveButtons();
})();
