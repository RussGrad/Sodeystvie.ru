/**
 * Избранное объектов (localStorage) — счётчик в шапке.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'sodeystvie-favorites';
    var LEGACY_KEY = 'sodeystvie:catalog-favs';

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

    function migrateLegacy() {
        try {
            var legacyRaw = localStorage.getItem(LEGACY_KEY);
            if (!legacyRaw) {
                return;
            }
            var legacy = JSON.parse(legacyRaw);
            if (!Array.isArray(legacy)) {
                localStorage.removeItem(LEGACY_KEY);
                return;
            }
            var ids = readIds();
            legacy.forEach(function (id) {
                id = String(id || '').trim();
                if (id && ids.indexOf(id) === -1) {
                    ids.push(id);
                }
            });
            writeIds(ids);
            localStorage.removeItem(LEGACY_KEY);
        } catch (e) {
            /* ignore */
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

    migrateLegacy();

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
        getCatalogUrl: function () {
            return '/catalog/?favorites=1';
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateBadge);
    } else {
        updateBadge();
    }
})();
