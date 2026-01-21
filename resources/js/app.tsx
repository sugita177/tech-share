import './bootstrap.js';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import LoginPage from './pages/LoginPage';

const App: React.FC = () => {
    return (
        <BrowserRouter>
            <Routes>
                {/* ログイン画面 */}
                <Route path="/login" element={<LoginPage />} />
                
                {/* 記事一覧（仮：後ほど作成） */}
                <Route path="/" element={<div>記事一覧（ログイン後に表示）</div>} />
            </Routes>
        </BrowserRouter>
    );
};

const container = document.getElementById('app');
if (container) {
    const root = createRoot(container);
    root.render(
        <React.StrictMode>
            <App />
        </React.StrictMode>
    );
}