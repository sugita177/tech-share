import React, { useEffect, useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import axiosClient from '../api/axiosClient';
import { Article, PaginatedResponse, PaginationData } from '../types/api';
import { Link } from 'react-router-dom';

const TimelinePage: React.FC = () => {
    const { logout } = useAuth();
    const [articles, setArticles] = useState<Article[]>([]);
    const [pagination, setPagination] = useState<PaginationData | null>(null);
    const [loading, setLoading] = useState(true);

    const fetchArticles = async (page: number = 1) => {
        try {
            // 型指定を response.data 全体に合わせておくと安全
            const response = await axiosClient.get<PaginatedResponse<Article>>(`/articles?page=${page}`);
            // response.data.data が実際の配列
            setArticles(response.data.data);
            // ページネーション情報を保存
            setPagination({
                current_page: response.data.meta.current_page,
                last_page: response.data.meta.last_page,
                prev_page_url: response.data.links.prev,
                next_page_url: response.data.links.next,
            });
            // データを読み込んだら、画面の最上部（x=0, y=0）に戻す
            window.scrollTo({ top: 0, behavior: 'auto' });
        } catch (error) {
            console.error('記事の取得に失敗しました', error);
        } finally {
            setLoading(false);
        }
    };
    
    useEffect(() => {
        fetchArticles();
    }, []);

    const getVisiblePages = (current: number, last: number) => {
        const range = 2;
        const pages: (number | string)[] = [];
        
        for (let i = 1; i <= last; i++) {
            if (i === 1 || i === last || (i >= current - range && i <= current + range)) {
                pages.push(i);
            } else if (i === current - range - 1 || i === current + range + 1) {
                // 直前がすでに '...' でない場合のみ追加する
                if (pages[pages.length - 1] !== '...') {
                    pages.push('...');
                }
            }
        }
        return pages;
    };

    const visiblePages = pagination ? getVisiblePages(pagination.current_page, pagination.last_page) : [];

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <nav className="flex justify-between items-center mb-8 bg-white p-4 rounded-xl shadow-sm">
                <h1 className="text-2xl font-extrabold text-sky-600">社内共有アプリ</h1>
                <div className="flex items-center gap-4">
                    <Link 
                        to="/my/articles" 
                        className="text-gray-600 font-bold hover:text-sky-600 transition"
                    >
                        自分の記事
                    </Link>

                    <button onClick={logout} className="bg-red-50 text-red-600 font-semibold px-4 py-2 rounded-lg hover:bg-red-100 transition">
                        ログアウト
                    </button>
                </div>
            </nav>

            <div className="max-w-4xl mx-auto">
                        <div className="flex justify-between items-center mb-6">
                <h2 className="text-xl font-bold text-gray-800">最新の記事</h2>
                <Link 
                    to="/articles/create" 
                    className="bg-sky-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-sky-700 transition shadow-sm"
                >
                    + 記事を書く
                </Link>
            </div>
                
                {loading ? (
                    <p className="text-center text-gray-500">読み込み中...</p>
                ) : (
                    <div className="grid gap-4">
                        {articles.map(article => (
                            <Link 
                                key={article.id} 
                                to={`/articles/${article.slug}`} // slugを使って遷移
                                className="block bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md hover:border-sky-200 transition group"
                            >
                                <h3 className="text-lg font-bold text-gray-900 group-hover:text-sky-600 transition">
                                    {article.title}
                                </h3>
                                <p className="text-gray-600 mt-2 line-clamp-2">
                                    {article.content}
                                </p>
                                <div className="mt-4 text-sm text-gray-400">
                                    {article.created_at}
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
            {/* ページネーションコントロール */}
            {pagination && pagination.last_page > 1 && (
                <div className="flex justify-center items-center gap-2 mt-10">
                    <button
                        onClick={() => fetchArticles(pagination.current_page - 1)}
                        disabled={!pagination.prev_page_url}
                        className="px-3 py-2 border rounded-md disabled:opacity-30 hover:bg-gray-100 transition"
                    >
                        &lt; 前へ
                    </button>

                    <div className="flex gap-1 items-center">
                        {visiblePages.map((page, index) => (
                            page === '...' ? (
                                <span key={`dots-${index}`} className="px-3 py-2 text-gray-400">...</span>
                            ) : (
                                <button
                                    key={page}
                                    onClick={() => fetchArticles(Number(page))}
                                    className={`px-4 py-2 border rounded-md transition ${
                                        pagination.current_page === page
                                            ? 'bg-sky-600 text-white border-sky-600'
                                            : 'bg-white text-gray-600 hover:bg-gray-100'
                                    }`}
                                >
                                    {page}
                                </button>
                            )
                        ))}
                    </div>
                    
                    <button
                        onClick={() => fetchArticles(pagination.current_page + 1)}
                        disabled={!pagination.next_page_url}
                        className="px-3 py-2 border rounded-md disabled:opacity-30 hover:bg-gray-100 transition"
                    >
                        次へ &gt;
                    </button>
                </div>
            )}
        </div>
    );
};

export default TimelinePage;