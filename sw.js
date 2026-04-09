const CACHE_NAME = 'atelier-cache-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/index.php',
  '/css/style.css',
  '/js/main.js',
  '/img/logo_1.svg',
  '/img/logo_2.svg',
  '/img/favicon.ico',
  '/store/data.csv'
];
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(ASSETS_TO_CACHE))
      .then(() => self.skipWaiting())
  );
});
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      );
    })
  );
  return self.clients.claim();
});
// Настоящий обработчик (Стратегия: Сначала сеть, потом кэш)
self.addEventListener('fetch', (event) => {
  // Игнорируем сторонние скрипты
  if (event.request.url.includes('yandex.ru')) return;
  // Игнорируем запросы, которые не являются GET (например, POST формы)
  if (event.request.method !== 'GET') return;
  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Если сервер ответил успешно, сохраняем свежую копию в кэш и отдаем юзеру
        return caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, networkResponse.clone());
          return networkResponse;
        });
      })
      .catch(() => {
        // Если у пользователя НЕТ ИНТЕРНЕТА, отдаем замороженную версию из кэша
        return caches.match(event.request);
      })
  );
});