self.addEventListener("push", (event) => {
    const notification = event.data.json();

    const notificationOptions = {
        body: notification.body,
        icon: notification.icon || "./images/logo.png",
        badge: "./images/badge.png",
        data: notification.data || { url: "/" },
        actions: notification.actions || [],
        requireInteraction: notification.type === 'prize_won', // Keep prize notifications visible
        tag: notification.data?.type || 'general',
        timestamp: Date.now(),
        vibrate: [200, 100, 200] // Vibration pattern for mobile
    };

    event.waitUntil(
        self.registration.showNotification(notification.title, notificationOptions)
    );
});

self.addEventListener("notificationclick", (event) => {
    event.notification.close();

    const urlToOpen = event.notification.data?.url || "/";

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if there's already a window/tab open with the target URL
                for (const client of clientList) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }

                // If no existing window/tab, open a new one
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Handle notification action clicks (like "View Results", "View Wallet")
self.addEventListener("notificationactionclick", (event) => {
    event.notification.close();

    let urlToOpen = "/";

    switch (event.action) {
        case 'view_tournament_results':
            urlToOpen = "/tournament/results";
            break;
        case 'view_peer_results':
            urlToOpen = "/peers/results";
            break;
        case 'view_wallet':
            urlToOpen = "/wallet";
            break;
        default:
            urlToOpen = event.notification.data?.url || "/";
    }

    event.waitUntil(
        clients.openWindow(urlToOpen)
    );
});
