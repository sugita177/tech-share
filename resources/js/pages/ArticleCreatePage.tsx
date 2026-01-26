import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axiosClient from '../api/axiosClient';

const ArticleCreatePage: React.FC = () => {
    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const navigate = useNavigate();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        try {
            // すでに作成済みのAPIへPOST
            await axiosClient.post('/articles', { title, content });
            alert('記事を投稿しました！');
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
                            className={`px-6 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 transition shadow-sm ${
                                isSubmitting ? 'opacity-50 cursor-not-allowed' : ''
                            }`}
                        >
                            {isSubmitting ? '投稿中...' : '公開する'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default ArticleCreatePage;