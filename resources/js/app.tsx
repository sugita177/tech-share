import './bootstrap.js';
import React from 'react';
import { createRoot } from 'react-dom/client';

const App: React.FC = () => {
    return (
        <div style={{ padding: '40px' }}>
            <h1 style={{ fontSize: '24px', fontWeight: 'bold' }}>
                社内共有アプリ (React + TypeScript)
            </h1>
            <p>Sail環境でのSPA構築が完了しました！</p>
        </div>
    );
};

// ここから下は「実行命令」
const container = document.getElementById('app');
if (container) {
    const root = createRoot(container);
    root.render(
        <React.StrictMode>
            <App />
        </React.StrictMode>
    );
}