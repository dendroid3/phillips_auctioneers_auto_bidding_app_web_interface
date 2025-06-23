import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import path from 'path';
import { defineConfig } from 'vite';
import environmentPlugin from 'vite-plugin-environment'; // ✅ Import the plugin

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts', 'resources/js/echo.js'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        environmentPlugin({ // ✅ Add plugin here
            VITE_PUSHER_APP_KEY: JSON.stringify(process.env.PUSHER_APP_KEY),
            VITE_PUSHER_APP_CLUSTER: JSON.stringify(process.env.PUSHER_APP_CLUSTER),
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
});
