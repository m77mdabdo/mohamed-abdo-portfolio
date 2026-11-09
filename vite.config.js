import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/site.css', 'resources/ts/site.ts'],
            refresh: true,
        }),
    ],
    build: {
        // three.js is split into its own chunk and loaded only when the hero scene starts
        chunkSizeWarningLimit: 700,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
