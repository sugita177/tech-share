import React from 'react';
import { useAuth } from '../contexts/AuthContext';

const DashboardPage: React.FC = () => {
    const { logout } = useAuth();

    return (
        <div className="p-8">
            <nav className="flex justify-between items-center mb-8 border-b pb-4">
                <h1 className="text-xl font-bold">社内共有アプリ</h1>
                <button 
                    onClick={logout}
                    className="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition"
                >
                    ログアウト
                </button>
            </nav>
            <div className="bg-white p-6 rounded shadow">
                <p className="text-gray-600">ようこそ！ここはログインしたユーザーだけが閲覧できるエリアです。</p>
            </div>
        </div>
    );
};

export default DashboardPage;