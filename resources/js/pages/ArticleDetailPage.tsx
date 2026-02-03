import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import axiosClient from '../api/axiosClient';
import { Article } from '../types/api';
import { useAuth } from '../contexts/AuthContext';


const ArticleDetailPage: React.FC = () => {
    const { slug } = useParams<{ slug: string }>();
    const { user } = useAuth(); // Contextからユーザー情報を取得
    const [article, setArticle] = useState<Article | null>(null);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    useEffect(() => {
        const fetchArticle = async () => {
            try {
                const response = await axiosClient.get(`/articles/${slug}`);
                // APIResource の構造に合わせて response.data.data となる場合があります
                setArticle(response.data.data || response.data);
            } catch (error) {
                console.error("記事の取得に失敗しました", error);
                alert("記事が見つかりませんでした。");
                navigate('/');
            } finally {
                setLoading(false);
            }
        };

        if (slug) fetchArticle();
    }, [slug, navigate]);

    // 追加：削除処理
    const handleDelete = async () => {
        if (!article) return;

        // ユーザーに確認を取る
        if (!window.confirm('この記事を完全に削除してもよろしいですか？')) {
            return;
        }

        try {
            // バックエンドの destroy(int $id) に合わせて ID を送信
            await axiosClient.delete(`/articles/${article.id}`);
            alert('記事を削除しました。');
            navigate('/'); // 一覧画面へ戻る
        } catch (error: any) {
            console.error("削除に失敗しました", error);
            alert(error.response?.data?.message || "削除に失敗しました。");
        }
    };

    // 自分の記事か、または管理者か（後で拡張可能）を判定
    const canEditOrDelete = user && article && (user.id === article.user_id || user.is_admin);

    if (loading) return <div className="text-center mt-10">読み込み中...</div>;
    if (!article) return null;

    return (
        // 1. 画面全体の背景色を薄いグレー(bg-gray-50)に変更し、高さを最小100%に
        <div className="min-h-screen bg-gray-50 py-12 px-4">
            <div className="max-w-3xl mx-auto">

                {/* 戻るボタンの配置を微調整 */}
                <Link 
                    to="/" 
                    className="inline-flex items-center text-sm font-medium text-gray-500 hover:text-sky-600 mb-6 transition"
                >
                    <span className="mr-1">←</span> 記事一覧に戻る
                </Link>

                {/* 2. 記事カード: 背景白、影を少し強調、角丸を大きく */}
                <article className="bg-white shadow-sm border border-gray-200 rounded-2xl overflow-hidden">

                    {/* ヘッダー部分に薄いアクセントを付ける場合 */}
                    <header className="p-8 pb-6 border-b border-gray-100">
                        <div className="flex justify-between items-start mb-4">
                            <h1 className="text-3xl font-extrabold text-gray-900 tracking-tight mb-4">
                                {article.title}
                            </h1>
                            {/* 管理者かつ他人の記事の場合にバッジを表示 */}
                            {user?.is_admin && user.id !== article.user_id && (
                                <span className="bg-purple-100 text-purple-700 text-xs font-bold px-3 py-1 rounded-full shadow-sm">
                                    管理者モードで閲覧中
                                </span>
                            )}
                        </div>
                        <div className="flex items-center text-sm text-gray-500">
                            <span className="bg-sky-50 text-sky-700 px-2 py-1 rounded md mr-3">
                                公開済み
                            </span>
                            <time dateTime={article.created_at}>
                                {new Date(article.created_at).toLocaleDateString('ja-JP', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                })}
                            </time>
                        </div>
                    </header>

                    {/* 3. 本文エリア: 余白をたっぷり取り、文字色を少し柔らかい黒に */}
                    <div className="p-8 pt-6">
                        <div className="text-gray-700 leading-relaxed text-lg whitespace-pre-wrap">
                            {article.content}
                        </div>
                    </div>

                    {/* フッター（必要に応じて編集ボタンなどを配置） */}
                    <footer className="px-8 py-6 bg-white border-t border-gray-200 flex justify-between items-center rounded-b-2xl">
                        <div className="text-xs text-gray-400">
                            最終更新: {new Date(article.created_at).toLocaleDateString()}
                        </div>
                        {/* isOwner が true の場合のみボタンを表示 */}
                        {canEditOrDelete && (
                            <div className="flex gap-4">
                                <button 
                                    onClick={() => navigate(`/articles/${slug}/edit`)} // 編集ページへ
                                    className="px-5 py-2 text-sm font-semibold text-sky-600 border border-sky-600 rounded-lg hover:bg-sky-50 transition"
                                >
                                    編集する
                                </button>
                                <button 
                                    onClick={handleDelete}
                                    className="px-5 py-2 text-sm font-semibold text-red-600 hover:text-red-700 transition"
                                >
                                    削除
                                </button>
                            </div>
                        )}
                    </footer>
                </article>
            </div>
        </div>
    );
};

export default ArticleDetailPage;