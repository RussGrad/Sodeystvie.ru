/**
 * Избранное объектов (localStorage) — счётчик в шапке.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'sodeystvie-favorites';

    function readIds() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                return [];
            }
            var parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) {
                return [];
            }
            return parsed.filter(function (id) {
                return typeof id === 'string' && id.trim() !== '';
            });
        } catch (e) {
            return [];
        }
    }

    function writeIds(ids) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
        } catch (e) {
            /* ignore quota */
        }
    }

    function updateBadge() {
        var el = document.getElementById('site-favorites-count');
        if (!el) {
            return;
        }
        var n = readIds().length;
        el.textContent = String(n);
        if (n > 0) {
            el.removeAttribute('hidden');
        } else {
            el.setAttribute('hidden', 'hidden');
        }
    }

    window.SodeystvieFavorites = {
        getIds: readIds,
        has: function (id) {
            return readIds().indexOf(String(id)) !== -1;
        },
        toggle: function (id) {
            id = String(id || '').trim();
            if (!id) {
                return false;
            }
            var ids = readIds();
            var i = ids.indexOf(id);
            if (i === -1) {
                ids.push(id);
                writeIds(ids);
                updateBadge();
                return true;
            }
            ids.splice(i, 1);
            writeIds(ids);
            updateBadge();
            return false;
        },
        refresh: updateBadge,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateBadge);
    } else {
        updateBadge();
    }
})();
