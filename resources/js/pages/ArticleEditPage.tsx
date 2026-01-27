import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axiosClient from '../api/axiosClient';

const ArticleEditPage: React.FC = () => {
    const { slug } = useParams<{ slug: string }>();
    const navigate = useNavigate();

    const [id, setId] = useState<number | null>(null); // 更新にはIDが必要
    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [articleSlug, setArticleSlug] = useState(''); // inputのslug用
    const [status, setStatus] = useState('draft');
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);

    // 1. 初期データの取得
    useEffect(() => {
        const fetchArticle = async () => {
            try {
                const response = await axiosClient.get(`/articles/${slug}`);
                const article = response.data.data;
                setId(article.id);
                setTitle(article.title);
                setContent(article.content);
                setArticleSlug(article.slug);
                setStatus(article.status); // BackendのArticleStatus Enumの値
            } catch (error) {
                alert('記事の取得に失敗しました');
                navigate('/');
            } finally {
                setLoading(false);
            }
        };
        fetchArticle();
    }, [slug, navigate]);

    // 2. 更新処理
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!id) return;

        setSubmitting(true);
        try {
            // Controllerの引数に合わせて PUT /articles/{id}
            await axiosClient.put(`/articles/${id}`, {
                title,
                content,
                slug: articleSlug,
                status
            });
            alert('記事を更新しました');
            navigate(`/articles/${articleSlug}`); // 更新後の詳細へ
        } catch (error: any) {
            console.error(error);
            alert(error.response?.data?.message || '更新に失敗しました');
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) return <div className="text-center mt-10">読み込み中...</div>;

    return (
        // 画面全体の背景を薄いグレーに
        <div className="min-h-screen bg-gray-50 py-12 px-4">
            <div className="max-w-3xl mx-auto">
                
                <header className="mb-8">
                    <h1 className="text-3xl font-extrabold text-gray-900 tracking-tight">
                        記事の編集
                    </h1>
                    <p className="text-gray-500 mt-2 text-sm">
                        内容を修正して「更新する」ボタンを押してください。
                    </p>
                </header>
        
                {/* フォーム全体を白いカード化 */}
                <form onSubmit={handleSubmit} className="bg-white shadow-sm border border-gray-200 rounded-2xl overflow-hidden">
                    <div className="p-8 space-y-6">
                        
                        {/* タイトル入力 */}
                        <div>
                            <label htmlFor="title" className="block text-sm font-bold text-gray-700 mb-2">
                                タイトル
                            </label>
                            <input
                                type="text"
                                id="title"
                                value={title}
                                onChange={(e) => setTitle(e.target.value)}
                                placeholder="記事のタイトルを入力"
                                className="w-full border border-gray-300 rounded-lg shadow-sm px-4 py-3 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                                required
                            />
                        </div>
        
                        {/* スラグとステータスの横並び（デスクトップ時） */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label htmlFor="slug" className="block text-sm font-bold text-gray-700 mb-2">
                                    スラグ (URL)
                                </label>
                                <div className="flex items-stretch shadow-sm rounded-lg overflow-hidden border border-gray-300 focus-within:ring-2 focus-within:ring-sky-500 focus-within:border-sky-500 transition">
                                    {/* 左側のラベル：borderを消して、親のborderに任せる */}
                                    <span className="flex items-center bg-gray-100 px-3 text-gray-500 text-sm border-r border-gray-300 whitespace-nowrap">
                                        /articles/
                                    </span>
                                    {/* 右側の入力欄：borderを消して、親のfocus管理に任せる */}
                                    <input
                                        type="text"
                                        id="slug"
                                        value={articleSlug}
                                        onChange={(e) => setArticleSlug(e.target.value)}
                                        className="flex-1 px-4 py-3 outline-none bg-white min-w-0"
                                    />
                                </div>
                            </div>
                            <div>
                                <label htmlFor="status" className="block text-sm font-bold text-gray-700 mb-2">
                                    公開ステータス
                                </label>
                                <select
                                    id="status"
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                    className="w-full border border-gray-300 rounded-lg shadow-sm px-4 py-3 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition bg-white"
                                >
                                    <option value="draft">下書き保存</option>
                                    <option value="published">今すぐ公開</option>
                                </select>
                            </div>
                        </div>
        
                        {/* 本文入力 */}
                        <div>
                            <label htmlFor="content" className="block text-sm font-bold text-gray-700 mb-2">
                                本文
                            </label>
                            <textarea
                                id="content"
                                value={content}
                                onChange={(e) => setContent(e.target.value)}
                                placeholder="共有したい内容を自由に書いてください..."
                                className="w-full border border-gray-300 rounded-lg shadow-sm px-4 py-3 h-80 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                                required
                            />
                        </div>
                    </div>
        
                    {/* 操作フッター部分：背景色を少し変えて「保存」を強調 */}
                    <div className="bg-gray-50 border-t border-gray-200 px-8 py-4 flex items-center justify-between">
                        <button
                            type="button"
                            onClick={() => navigate(-1)}
                            className="text-sm font-semibold text-gray-500 hover:text-gray-700 transition"
                        >
                            キャンセルして戻る
                        </button>
                        
                        <div className="flex gap-3">
                            <button
                                type="submit"
                                disabled={submitting}
                                className="bg-sky-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-sky-700 disabled:opacity-50 transition shadow-sm"
                            >
                                {submitting ? '保存中...' : '更新を保存する'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default ArticleEditPage;