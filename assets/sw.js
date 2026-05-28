/* ChatAssist - Service Worker */
self.addEventListener('install', function(e) {
  e.waitUntil(self.skipWaiting());
});
self.addEventListener('activate', function(e) {
  e.waitUntil(self.clients.claim());
});
self.addEventListener('push', function(e) {
  var data = {};
  try {
    data = e.data ? e.data.json() : {};
  } catch (err) {}
  var title = data.title || 'New message';
  var body = data.body || '';
  var icon = data.icon || '';
  var options = { body: body };
  if (icon) options.icon = icon;
  e.waitUntil(self.registration.showNotification(title, options));
});
self.addEventListener('notificationclick', function(e) {
  e.notification.close();
  e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
    for (var i = 0; i < clientList.length; i++) {
      if (clientList[i].focus) {
        clientList[i].focus();
        return;
      }
    }
    if (self.clients.openWindow) {
      self.clients.openWindow('/');
    }
  }));
});
