import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axiosClient from '../api/axiosClient';

const ArticleCreatePage: React.FC = () => {
    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [status, setStatus] = useState<'published' | 'draft'>('draft');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const navigate = useNavigate();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        try {
            // APIへPOST
            await axiosClient.post('/articles', {
                title,
                content, 
                status // 'published' or 'draft'
            });
            const message = status === 'published' ? '記事を公開しました！' : '記事を下書き保存しました！';
            alert(message);
            navigate('/'); // 投稿後は一覧へ
        } catch (error) {
            console.error(error);
            alert('投稿に失敗しました。入力内容を確認してください。');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="max-w-4xl mx-auto p-6">
            <div className="bg-white shadow-md rounded-lg p-8">
                <h1 className="text-2xl font-bold mb-6 text-gray-800">新規記事の作成</h1>
                
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div>
                        <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-2">
                            タイトル
                        </label>
                        <input
                            type="text"
                            id="title"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            className="w-full border border-gray-300 p-3 rounded-md focus:ring-2 focus:ring-sky-500 outline-none"
                            placeholder="記事のタイトルを入力"
                            required
                        />
                    </div>

                    <div>
                        <label htmlFor="content" className="block text-sm font-medium text-gray-700 mb-2">
                            本文
                        </label>
                        <textarea
                            id="content"
                            rows={12}
                            value={content}
                            onChange={(e) => setContent(e.target.value)}
                            className="w-full border border-gray-300 p-3 rounded-md focus:ring-2 focus:ring-sky-500 outline-none"
                            placeholder="どのような内容を共有しますか？"
                            required
                        />
                    </div>

                    {/* 公開設定のラジオボタン */}
                    <div>
                        <span className="block text-sm font-medium text-gray-700 mb-3">公開設定</span>
                        <div className="flex gap-6">
                            <label className={`flex items-center space-x-2 cursor-pointer p-3 border rounded-md transition ${status === 'draft' ? 'bg-gray-50 border-gray-400' : 'border-gray-200'}`}>
                                <input 
                                    type="radio" 
                                    name="status"
                                    value="draft" 
                                    checked={status === 'draft'} 
                                    onChange={() => setStatus('draft')} 
                                    className="w-4 h-4 text-gray-600 focus:ring-gray-500"
                                />
                                <span className="text-gray-700">下書き保存</span>
                            </label>

                            <label className={`flex items-center space-x-2 cursor-pointer p-3 border rounded-md transition ${status === 'published' ? 'bg-sky-50 border-sky-500' : 'border-gray-200'}`}>
                                <input 
                                    type="radio" 
                                    name="status"
                                    value="published" 
                                    checked={status === 'published'} 
                                    onChange={() => setStatus('published')} 
                                    className="w-4 h-4 text-sky-600 focus:ring-sky-500"
                                />
                                <span className="text-gray-900 font-medium">公開する</span>
                            </label>
                        </div>
                    </div>

                    <div className="flex justify-end gap-4">
                        <button
                            type="button"
                            onClick={() => navigate(-1)}
                            className="px-6 py-2 text-gray-600 hover:text-gray-800"
                        >
                            キャンセル
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            // 状態に応じてボタンの色とテキストを変える（視認性の向上）
                            className={`px-8 py-2 text-white rounded-md transition shadow-sm font-bold ${
                                isSubmitting 
                                    ? 'bg-gray-400 cursor-not-allowed' 
                                    : status === 'published' 
                                        ? 'bg-sky-600 hover:bg-sky-700' // 公開時：青
                                        : 'bg-gray-600 hover:bg-gray-700' // 下書き時：グレー
                            }`}
                        >
                            {isSubmitting 
                                ? '送信中...' 
                                : status === 'published' ? '公開する' : '下書き保存する'
                            }
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default ArticleCreatePage;