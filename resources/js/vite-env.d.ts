/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_API_BASE_URL: string;
    readonly VITE_SANCTUM_CSRF_URL: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}