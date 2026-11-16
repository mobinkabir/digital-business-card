/* Service worker for the digital business card.
 *
 * Strategy
 *   - navigations + JSON data : network first (fresh content when online,
 *     last good copy when offline)
 *   - css / js / fonts / icons: stale-while-revalidate with query-insensitive
 *     matching, so `card.js?v=49` still resolves to the cached file
 *   - panel routes are never cached: admin pages must always come from the
 *     server, otherwise a stale panel can post to the wrong form.
 */
const VERSION = 'v17';
const SHELL_CACHE = 'card-shell-' + VERSION;
const DATA_CACHE = 'card-data-' + VERSION;

const PRECACHE = [
  './',
  './index.html',
  './css/tokens.css',
  './css/fonts.css',
  './css/fonts-catalog.css',
  './css/print.css',
  './js/app-shell.js',
  './js/card.js',
  './manifest.json',
  './image/logo.png',
  './image/icon-192.png',
  './image/icon-512.png',
  './image/apple-touch-icon.png',
  './image/favicon.ico'
];

const DATA_URLS = ['card.json', 'content-site.json', 'views.json'];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(SHELL_CACHE).then(function (cache) {
      // One missing file must never fail the whole install.
      return Promise.all(PRECACHE.map(function (url) {
        return cache.add(url).catch(function () { return null; });
      }));
    }).then(function () { return self.skipWaiting(); })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.map(function (key) {
        if (key !== SHELL_CACHE && key !== DATA_CACHE) return caches.delete(key);
        return null;
      }));
    }).then(function () { return self.clients.claim(); })
  );
});

self.addEventListener('message', function (event) {
  if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();
});

function isDataRequest(url) {
  return DATA_URLS.some(function (name) { return url.pathname.indexOf(name) !== -1; });
}

function isPanel(url) {
  return /\/admin(\/|$)/.test(url.pathname) || /\/(login|logout|backup|resume)\.php$/.test(url.pathname);
}

function networkFirst(request, cacheName) {
  return fetch(request).then(function (response) {
    if (response && response.ok && cacheName) {
      const copy = response.clone();
      caches.open(cacheName).then(function (cache) { cache.put(request, copy); });
    }
    return response;
  }).catch(function () {
    return caches.match(request).then(function (cached) {
      if (cached) return cached;
      if (request.mode === 'navigate') {
        return caches.match('./index.html', { ignoreSearch: true });
      }
      return new Response('', { status: 503, statusText: 'offline' });
    });
  });
}

function staleWhileRevalidate(request) {
  return caches.match(request, { ignoreSearch: true }).then(function (cached) {
    const network = fetch(request).then(function (response) {
      if (response && response.ok) {
        const copy = response.clone();
        caches.open(SHELL_CACHE).then(function (cache) { cache.put(request, copy); });
      }
      return response;
    }).catch(function () { return cached; });
    return cached || network;
  });
}

self.addEventListener('fetch', function (event) {
  if (event.request.method !== 'GET') return;

  let url;
  try { url = new URL(event.request.url); } catch (e) { return; }
  if (url.origin !== self.location.origin) return;
  if (isPanel(url)) return;
  // Never cache any PHP endpoint (views.php, resume.php, backup.php, ...).
  if (/\.php$/i.test(url.pathname)) return;

  if (isDataRequest(url)) {
    event.respondWith(networkFirst(event.request, DATA_CACHE));
    return;
  }

  if (event.request.mode === 'navigate') {
    event.respondWith(networkFirst(event.request, SHELL_CACHE));
    return;
  }

  event.respondWith(staleWhileRevalidate(event.request));
});
