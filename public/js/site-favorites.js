/**
 * Избранное объектов (localStorage) — счётчик в шапке.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'sodeystvie-favorites';
    var LEGACY_KEY = 'sodeystvie:catalog-favs';

    function normalizeId(id) {
        return String(id == null ? '' : id).trim();
    }

    function normalizeIds(list) {
        if (!Array.isArray(list)) {
            return [];
        }
        var out = [];
        list.forEach(function (id) {
            id = normalizeId(id);
            if (id && out.indexOf(id) === -1) {
                out.push(id);
            }
        });
        return out;
    }

    function readRaw(key) {
        try {
            var raw = localStorage.getItem(key);
            if (!raw) {
                return [];
            }
            var parsed = JSON.parse(raw);
            return normalizeIds(parsed);
        } catch (e) {
            return [];
        }
    }

    function readIds() {
        var ids = readRaw(STORAGE_KEY);
        var legacy = readRaw(LEGACY_KEY);
        if (legacy.length === 0) {
            return ids;
        }
        legacy.forEach(function (id) {
            if (ids.indexOf(id) === -1) {
                ids.push(id);
            }
        });
        writeIds(ids);
        try {
            localStorage.removeItem(LEGACY_KEY);
        } catch (e) {
            /* ignore */
        }
        return ids;
    }

    function writeIds(ids) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(normalizeIds(ids)));
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
        setIds: function (ids) {
            writeIds(ids);
            updateBadge();
        },
        removeIds: function (ids) {
            var remove = normalizeIds(ids);
            if (remove.length === 0) {
                return;
            }
            writeIds(readIds().filter(function (id) {
                return remove.indexOf(id) === -1;
            }));
            updateBadge();
        },
        has: function (id) {
            return readIds().indexOf(normalizeId(id)) !== -1;
        },
        toggle: function (id) {
            id = normalizeId(id);
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
