import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Three.js flight / group presentation (only loaded on the present pages)
                'resources/js/presentation/main.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Three.js alone is ~650 kB; it only loads on the presentation pages.
        chunkSizeWarningLimit: 1000,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
