import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: '0.0.0.0', // コンテナ外からの接続を許可
        hmr: {
            host: 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    test: {
        globals: true,           // describe や expect をグローバルで使えるようにする
        environment: 'jsdom',    // ブラウザ環境をシミュレート
        setupFiles: ['./resources/js/test/setup.ts'], // 初期設定ファイル（後述）
    },
});
