/**
 * Онлайн-чат витрины → /api/site-chat.php → CRM messenger.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-site-chat-root]');
  if (!root) {
    return;
  }

  var enabled = root.getAttribute('data-enabled') === '1';
  var panel = root.querySelector('.site-chat__panel');
  var messagesEl = document.getElementById('site-chat-messages');
  var form = document.getElementById('site-chat-form');
  var input = document.getElementById('site-chat-input');
  var sendBtn = document.getElementById('site-chat-send');
  var errorEl = document.getElementById('site-chat-error');
  var STORAGE_KEY = 'sodeystvie-site-chat-token';
  var POLL_MS = 4000;
  var pollTimer = null;
  var lastSince = '';
  var messageIds = Object.create(null);
  var isOpen = false;

  function generateToken() {
    if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
      var bytes = new Uint8Array(24);
      window.crypto.getRandomValues(bytes);
      var bin = '';
      for (var i = 0; i < bytes.length; i++) {
        bin += String.fromCharCode(bytes[i]);
      }
      return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '').slice(0, 32);
    }
    var s = '';
    var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
    for (var j = 0; j < 32; j++) {
      s += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return s;
  }

  function getToken() {
    try {
      var stored = localStorage.getItem(STORAGE_KEY);
      if (stored && /^[a-zA-Z0-9_-]{16,64}$/.test(stored)) {
        return stored;
      }
    } catch (e) {
      /* ignore */
    }
    var token = generateToken();
    try {
      localStorage.setItem(STORAGE_KEY, token);
    } catch (err) {
      /* ignore */
    }
    return token;
  }

  function escapeHtml(text) {
    return String(text || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatTime(iso) {
    try {
      return new Date(iso).toLocaleString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: 'short',
      });
    } catch (e) {
      return '';
    }
  }

  function hideWelcome() {
    var welcome = messagesEl && messagesEl.querySelector('.site-chat__welcome');
    if (welcome) {
      welcome.remove();
    }
  }

  function appendMessage(msg) {
    if (!messagesEl || !msg || !msg.id || messageIds[msg.id]) {
      return;
    }
    messageIds[msg.id] = true;
    hideWelcome();

    var outgoing = msg.direction === 'inbound';
    var bubble = document.createElement('article');
    bubble.className = 'site-chat__bubble' + (outgoing ? ' site-chat__bubble--out' : ' site-chat__bubble--in');
    bubble.dataset.messageId = msg.id;

    var author = outgoing ? 'Вы' : msg.authorName || 'Оператор';
    bubble.innerHTML =
      '<div class="site-chat__bubble-author">' +
      escapeHtml(author) +
      '</div>' +
      '<div class="site-chat__bubble-text">' +
      escapeHtml(msg.body) +
      '</div>' +
      '<time class="site-chat__bubble-time" datetime="' +
      escapeHtml(msg.createdAt) +
      '">' +
      escapeHtml(formatTime(msg.createdAt)) +
      '</time>';

    messagesEl.appendChild(bubble);
    messagesEl.scrollTop = messagesEl.scrollHeight;

    if (msg.createdAt && (!lastSince || msg.createdAt > lastSince)) {
      lastSince = msg.createdAt;
    }
  }

  function showError(text) {
    if (!errorEl) {
      return;
    }
    if (!text) {
      errorEl.hidden = true;
      errorEl.textContent = '';
      return;
    }
    errorEl.textContent = text;
    errorEl.hidden = false;
  }

  function fetchMessages(silent) {
    if (!enabled) {
      return Promise.resolve();
    }
    if (
      input instanceof HTMLTextAreaElement &&
      document.activeElement === input &&
      input.value.trim().length > 0
    ) {
      return Promise.resolve();
    }
    var url =
      '/api/site-chat.php?visitorToken=' +
      encodeURIComponent(getToken()) +
      (lastSince ? '&since=' + encodeURIComponent(lastSince) : '');

    return fetch(url, {
      method: 'GET',
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, data: data };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data || result.data.ok === false) {
          throw new Error((result.data && result.data.error) || 'Не удалось загрузить сообщения');
        }
        var list = result.data.messages || [];
        list.forEach(appendMessage);
      })
      .catch(function (err) {
        if (isOpen && !silent) {
          showError(err.message || 'Ошибка связи с сервером');
        }
      });
  }

  function startPolling() {
    stopPolling();
    pollTimer = window.setInterval(function () {
      fetchMessages(true);
    }, POLL_MS);
  }

  function stopPolling() {
    if (pollTimer) {
      window.clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  function openChat() {
    isOpen = true;
    root.removeAttribute('inert');
    root.setAttribute('aria-hidden', 'false');
    document.body.classList.add('has-site-chat');
    window.requestAnimationFrame(function () {
      root.classList.add('site-chat--open');
    });
    if (!enabled) {
      showError('Чат временно недоступен. Позвоните нам или оставьте заявку на странице контактов.');
      return;
    }
    showError('');
    fetchMessages().then(startPolling);
    window.setTimeout(function () {
      if (input) {
        input.focus();
      } else if (panel) {
        panel.focus();
      }
    }, 80);
  }

  function closeChat() {
    if (!root.classList.contains('site-chat--open')) {
      return;
    }
    isOpen = false;
    root.classList.remove('site-chat--open');
    stopPolling();
    window.setTimeout(function () {
      root.setAttribute('aria-hidden', 'true');
      root.setAttribute('inert', '');
      document.body.classList.remove('has-site-chat');
    }, 280);
  }

  document.addEventListener('click', function (e) {
    var t = e.target;
    if (!t || !t.closest) {
      return;
    }
    if (t.closest('[data-site-chat-open]')) {
      e.preventDefault();
      openChat();
      return;
    }
    if (t.closest('[data-site-chat-close]')) {
      e.preventDefault();
      closeChat();
    }
  });

  document.addEventListener(
    'keydown',
    function (e) {
      if (e.key === 'Escape' && isOpen) {
        e.preventDefault();
        closeChat();
      }
    },
    true,
  );

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!enabled) {
        showError('Чат временно недоступен. Позвоните нам или оставьте заявку на странице контактов.');
        return;
      }
      if (!input) {
        return;
      }
      var text = input.value.trim();
      if (text === '') {
        return;
      }

      showError('');
      if (sendBtn instanceof HTMLButtonElement) {
        sendBtn.disabled = true;
      }

      fetch('/api/site-chat.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          visitorToken: getToken(),
          body: text,
          pageUrl: window.location.pathname + window.location.search,
          company: '',
        }),
      })
        .then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok, data: data };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data || result.data.ok === false) {
            throw new Error((result.data && result.data.error) || 'Не удалось отправить');
          }
          input.value = '';
          if (result.data.message) {
            appendMessage(result.data.message);
          }
          return fetchMessages();
        })
        .catch(function (err) {
          showError(err.message || 'Не удалось отправить сообщение');
        })
        .finally(function () {
          if (sendBtn instanceof HTMLButtonElement) {
            sendBtn.disabled = false;
          }
          if (input) {
            input.focus();
          }
        });
    });
  }

  if (input instanceof HTMLTextAreaElement) {
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (form) {
          form.requestSubmit();
        }
      }
    });
  }
})();
