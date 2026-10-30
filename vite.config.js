import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/public.js',
                // Alpine autonome pour les pages sans Livewire (~15 Ko au lieu
                // des ~237 Ko de Livewire sur chaque page du dashboard)
                'resources/js/alpine-app.js',
                // Chargé uniquement sur les formulaires avec éditeur riche
                'resources/js/quill-editor.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'alpine': ['alpinejs'],
                    'quill': ['quill'],
                },
                assetFileNames: (assetInfo) => {
                    let extType = assetInfo.name.split('.').at(1);
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        extType = 'img';
                    }
                    return `assets/${extType}/[name]-[hash][extname]`;
                },
                chunkFileNames: 'assets/js/[name]-[hash].js',
                entryFileNames: 'assets/js/[name]-[hash].js',
            },
        },
        cssCodeSplit: true,
        cssMinify: true,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.info', 'console.debug'],
            },
            mangle: {
                safari10: true,
            },
            format: {
                comments: false,
            },
        },
        reportCompressedSize: false,
        chunkSizeWarningLimit: 1000,
    },
    optimizeDeps: {
        include: ['alpinejs'],
    },
});
