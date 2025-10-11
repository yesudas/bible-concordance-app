// A "no-op" service worker

self.addEventListener("install", event => {
  // Activate immediately without caching
  self.skipWaiting();
});

self.addEventListener("activate", event => {
  // Take control of clients right away
  event.waitUntil(self.clients.claim());
});

// No fetch handler = browser loads everything directly from the network
