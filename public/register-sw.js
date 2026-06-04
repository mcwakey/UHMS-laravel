const UHMS_SW_CACHE_FIX_VERSION = '2026-06-04-tabler-icons-cache';

async function clearStaleUhmsCaches() {
    if (!('caches' in window)) {
        return false;
    }

    const keys = await caches.keys();
    const staleKeys = keys.filter((key) => (
        key === 'uhms-static-v1'
        || key === 'uhms-static-assets'
        || key.startsWith('workbox-precache')
    ));

    await Promise.all(staleKeys.map((key) => caches.delete(key)));

    return staleKeys.length > 0;
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const hadStaleCaches = await clearStaleUhmsCaches();
            const registration = await navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' });

            await registration.update();

            const refreshKey = `uhms-sw-refreshed:${UHMS_SW_CACHE_FIX_VERSION}`;

            if (hadStaleCaches && window.sessionStorage && !sessionStorage.getItem(refreshKey)) {
                sessionStorage.setItem(refreshKey, '1');
                window.location.reload();
            }
        } catch (error) {
            console.warn('UHMS service worker registration failed.', error);
        }
    });
}
