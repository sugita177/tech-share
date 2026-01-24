import React, { useEffect, useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import axiosClient from '../api/axiosClient';
import { Article } from '../types/api';

const DashboardPage: React.FC = () => {
    const { logout } = useAuth();
    const [articles, setArticles] = useState<Article[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchArticles = async () => {
            try {
                // 型指定を response.data 全体に合わせておくと安全です
                const response = await axiosClient.get<{ data: Article[] }>('/articles');

                console.log("Raw Data from API:", response.data.data[0]);
                // response.data.data が実際の配列です
                setArticles(response.data.data);
            } catch (error) {
                console.error('記事の取得に失敗しました', error);
            } finally {
                setLoading(false);
            }
        };
        fetchArticles();
    }, []);

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <nav className="flex justify-between items-center mb-8 bg-white p-4 rounded-xl shadow-sm">
                <h1 className="text-2xl font-extrabold text-sky-600">社内共有アプリ</h1>
                <button onClick={logout} className="bg-red-50 text-red-600 font-semibold px-4 py-2 rounded-lg hover:bg-red-100 transition">
                    ログアウト
                </button>
            </nav>

            <div className="max-w-4xl mx-auto">
                <h2 className="text-xl font-bold mb-6 text-gray-800">最新の記事</h2>
                
                {loading ? (
                    <p className="text-center text-gray-500">読み込み中...</p>
                ) : (
                    <div className="grid gap-4">
                        {articles.map(article => (
                            <div key={article.id} className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                                <h3 className="text-lg font-bold text-gray-900">{article.title}</h3>
                                <p className="text-gray-600 mt-2 line-clamp-2">{article.content}</p>
                                <div className="mt-4 text-sm text-gray-400">
                                    {article.created_at}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

export default DashboardPage;