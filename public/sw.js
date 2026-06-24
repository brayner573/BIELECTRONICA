const CACHE_NAME = 'faxel-bi-cache-v7';
const ASSETS_TO_CACHE = [
  './assets/css/main.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js',
  'assets/img/logo-192.png',
  'assets/img/logo-512.png'
];

// Install Event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

// Activate Event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  // Evitar interceptar peticiones POST, APIs, streams, ejecuciones, o la ruta de cerrar sesión
  if (
    event.request.method !== 'GET' || 
    url.pathname.includes('/api/') || 
    url.pathname.includes('/ejecutar') || 
    url.pathname.includes('/chat') || 
    url.pathname.includes('/stream') || 
    url.pathname.includes('/logout')
  ) {
    return;
  }

  // Comprobar si el recurso solicitado es un archivo estático pesado (estilos, scripts, imágenes, fuentes)
  const isStatic = (
    url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|otf|json)$/i) ||
    url.pathname.includes('/assets/') ||
    url.href.includes('cdn.jsdelivr.net')
  );

  // Si NO es un recurso estático (es decir, es una página HTML dinámica como dashboard, reportes, alertas, etc.),
  // la cargamos SIEMPRE de la red directamente para evitar CSRF obsoletos y consumo innecesario de caché.
  if (!isStatic) {
    return;
  }

  // Para recursos estáticos, usamos la estrategia Stale-While-Revalidate
  event.respondWith(
    caches.match(event.request).then(cachedResponse => {
      if (cachedResponse) {
        // Actualizar en segundo plano
        fetch(event.request).then(networkResponse => {
          if (networkResponse.status === 200) {
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, networkResponse));
          }
        }).catch(() => {});
        return cachedResponse;
      }
      
      return fetch(event.request).then(response => {
        if (response.status === 200) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
        }
        return response;
      });
    })
  );
});
