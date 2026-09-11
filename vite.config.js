import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny, google, local } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/app/theme.css',
                'app-modules/he4rt/resources/css/theme.css',
                'app-modules/docs/resources/css/theme.css',
                'app-modules/portal/resources/css/retrospective.css',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Nunito', {
                    weights: [400, 600, 700],
                }),
                google('Fraunces', {
                    weights: [400, 500, 600, 700],
                    styles: ['normal', 'italic'],
                }),
                google('Hanken Grotesk', {
                    weights: [400, 500, 600, 700],
                }),
                google('JetBrains Mono', {
                    weights: [400, 500, 700],
                }),
                local('Satoshi', {
                    variants: [
                        { src: 'app-modules/he4rt/resources/fonts/satoshi/Satoshi-Light.woff2', weight: 300 },
                        { src: 'app-modules/he4rt/resources/fonts/satoshi/Satoshi-Regular.woff2', weight: 400 },
                        { src: 'app-modules/he4rt/resources/fonts/satoshi/Satoshi-Medium.woff2', weight: 500 },
                        { src: 'app-modules/he4rt/resources/fonts/satoshi/Satoshi-Bold.woff2', weight: 700 },
                    ],
                }),
                local('Fira-Code', {
                    src: 'app-modules/he4rt/resources/fonts/fira-code/FiraCode-Regular.ttf',
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
    build: {
        minify: 'oxc',
        cssMinify: true,
        chunkSizeWarningLimit: 1600,
        reportCompressedSize: false,
        rolldownOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        // Creates chunks based on the package name
                        return id.toString().split('node_modules/')[1].split('/')[0].toString();
                    }
                },
            },
        },
    },
});
