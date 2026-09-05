import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        vue(),
        tailwindcss(),
    ],
    server: {
        // Listen on IPv4+IPv6 so @vite URLs work whether the app is opened via
        // 127.0.0.1 or localhost (::1). Default can bind IPv6-only on macOS.
        host: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
