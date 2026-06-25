import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

const supportsPwaBuild = Number.parseInt(process.versions.node.split('.')[0], 10) >= 20;

export default defineConfig({
    build: {
        manifest: 'manifest.json',
        rtl: true,
        outDir: 'public/build/',
        cssCodeSplit: true,
        rollupOptions: {
            output: {
                assetFileNames: (css) => {
                    if (css.name.split('.').pop() == 'css') {
                        return 'css/' + `[name]` + '.min.' + 'css';
                    } else {
                        return 'icons/' + css.name;
                    }
                },
                entryFileNames: 'js/' + `[name]` + `.[hash].bundle.js`,
                chunkFileNames: 'js/' + `[name]` + `.[hash].js`,
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/style.css',
                'resources/js/script.js',
                'resources/js/inertia.js',
            ],
            refresh: true,
        }),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),

        supportsPwaBuild && VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            includeAssets: [
                'favicon.ico',
                'robots.txt',
                'build/img/pwa/icon-192.png',
                'build/img/pwa/icon-512.png',
            ],
            manifest: {
                name: 'UHMS - Ultimate Hospital Management System',
                short_name: 'UHMS',
                description: 'Hospital Management System for clinics and hospitals.',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                orientation: 'any',
                background_color: '#ffffff',
                theme_color: '#0d6efd',
                lang: 'en',
                icons: [
                    { src: '/build/img/pwa/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
                    { src: '/build/img/pwa/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
                    { src: '/build/img/pwa/icon-maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                // Cache only static, non-sensitive app shell assets.
                globPatterns: [
                    '*.{js,css,ico,png,svg,webmanifest}',
                    'js/**/*.{js,css}',
                    'css/*.css',
                ],
                globIgnores: [
                    '**/plugins/**',
                    '**/plugins/tabler-icons/**/*.ttf',
                    '**/plugins/tabler-icons/**/*.eot',
                    '**/plugins/icons/remix/remixicon.svg',
                    '**/img/blogs/blog-details-img-01.svg',
                ],
                navigateFallback: null,
                // Sensitive routes must NEVER be served from cache.
                navigateFallbackDenylist: [
                    /^\/admin\//,
                    /^\/doctor\//,
                    /^\/staff\//,
                    /^\/api\//,
                    /^\/login/,
                    /^\/logout/,
                ],
                runtimeCaching: [
                    {
                        urlPattern: ({ url }) =>
                            url.pathname.startsWith('/build/js/') ||
                            url.pathname.startsWith('/build/css/') ||
                            url.pathname === '/build/manifest.json' ||
                            url.pathname === '/build/registerSW.js' ||
                            url.pathname === '/build/sw.js',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'uhms-build-code',
                            networkTimeoutSeconds: 3,
                            expiration: { maxEntries: 80, maxAgeSeconds: 60 * 60 * 24 },
                        },
                    },
                    {
                        urlPattern: ({ url }) =>
                            url.pathname.startsWith('/build/') ||
                            /\.(png|jpe?g|svg|gif|webp|ico|woff2?|ttf|eot)$/i.test(url.pathname),
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'uhms-static-assets',
                            expiration: { maxEntries: 200, maxAgeSeconds: 60 * 60 * 24 * 30 },
                        },
                    },
                    {
                        urlPattern: /^https:\/\/fonts\.(googleapis|gstatic)\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'uhms-fonts',
                            expiration: { maxEntries: 30, maxAgeSeconds: 60 * 60 * 24 * 365 },
                        },
                    },
                ],
                cleanupOutdatedCaches: true,
            },
            devOptions: {
                // Disabled in dev to prevent stale service-worker headaches
                enabled: false,
            },
        }),
    ].filter(Boolean),
});
