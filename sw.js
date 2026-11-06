/*
 * newsalat sw.js
 * - push, click, offline
 * - + Background Sync (sync)
 * - + Periodic Background Sync (periodicsync)
 */

const CACHE = 'newsalat-v3';
const PAGES = ['/', '/index.html', '/manifest.json', '/icons/icon-192.png', '/icons/icon-512.png'];

/* ---------- install / update ---------- */
self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(PAGES)));
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

/* ---------- offline ---------- */
self.addEventListener('fetch', e => {
  const req = e.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  const isPage = url.origin === location.origin && !url.pathname.endsWith('api.php');
  const isFeed = url.pathname.endsWith('api.php') && url.searchParams.get('action') === 'articles';
  if (!isPage && !isFeed) return;

  e.respondWith(
    fetch(req)
      .then(res => {
        const copy = res.clone();
        caches.open(CACHE).then(c => c.put(req, copy));
        return res;
      })
      .catch(() => caches.match(req))
  );
});

/* ---------- push ---------- */
self.addEventListener('push', e => {
  let data = {title: 'newsalat', body: 'New story', url: '/'};
  try { data = Object.assign(data, e.data.json()); } catch (err) {}

  e.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      tag: 'newsalat',
      icon: '/icons/icon-192.png',
      badge: '/icons/icon-192.png',
      data: {url: data.url}
    })
  );
});

/* ---------- tap ---------- */
self.addEventListener('notificationclick', e => {
  e.notification.close();
  const target = new URL(e.notification.data.url || '/', self.registration.scope).href;
  e.waitUntil(
    clients.matchAll({type: 'window', includeUncontrolled: true}).then(list => {
      for (const c of list) {
        if (c.url.startsWith(self.registration.scope) && 'focus' in c) {
          return c.focus();
        }
      }
      return clients.openWindow(target);
    })
  );
});

/* ---------- NEW: Background Sync - retries when back online ---------- */
self.addEventListener('sync', e => {
  if (e.tag === 'sync-newsalat') {
    e.waitUntil(
     fetch('/api/api.php?action=articles')
        .then(res => res.json())
        .then(data => caches.open(CACHE).then(c => c.put('/api/api.php?action=articles', new Response(JSON.stringify(data)))))
        .catch(() => {})
    );
  }
});

/* ---------- NEW: Periodic Background Sync - fresh news in background ---------- */
self.addEventListener('periodicsync', e => {
  if (e.tag === 'update-news') {
    e.waitUntil(
    fetch('/api/api.php?action=articles')
        .then(res => res.json())
        .then(data => {
          return caches.open(CACHE).then(c => c.put('/api/api.php?action=articles', new Response(JSON.stringify(data))));
        })
        .catch(() => {})
    );
  }
});