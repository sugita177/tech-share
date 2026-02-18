import './bootstrap.js';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Link } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { PrivateRoute } from './components/PrivateRoute';
import LoginPage from './pages/LoginPage';
import TimelinePage from './pages/TimelinePage';
import ArticleCreatePage from './pages/ArticleCreatePage';
import ArticleDetailPage from './pages/ArticleDetailPage';
import ArticleEditPage from './pages/ArticleEditPage';

const App: React.FC = () => {
    return (
        <AuthProvider>
            <BrowserRouter>
                <Routes>
                    <Route path="/login" element={<LoginPage />} />
                    <Route path="/" element={<PrivateRoute><TimelinePage /></PrivateRoute>} />
                    <Route path="/articles/create" element={<PrivateRoute><ArticleCreatePage /></PrivateRoute>} />
                    <Route path="/articles/:slug" element={<PrivateRoute><ArticleDetailPage /></PrivateRoute>} />
                    <Route path="/articles/:slug/edit" element={<PrivateRoute><ArticleEditPage /></PrivateRoute>} />

                    <Route path="*" element={
                        <div className="text-center mt-20">
                            <h1 className="text-4xl font-bold">404</h1>
                            <p className="text-gray-500">お探しのページは見つかりませんでした。</p>
                            <Link to="/" className="text-sky-600 underline">トップへ戻る</Link>
                        </div>
                    } />
                </Routes>
            </BrowserRouter>
        </AuthProvider>
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