import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom'; // 画面遷移用
import axiosClient from '../api/axiosClient';
import { type LoginResponse } from '../types/api';
import { useAuth } from '../contexts/AuthContext';

const LoginPage: React.FC = () => {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const { login, isAuthenticated } = useAuth();
    const navigate = useNavigate();

    // すでにログイン済みならトップへ飛ばす
    React.useEffect(() => {
        if (isAuthenticated) navigate('/');
    }, [isAuthenticated, navigate]);
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            // LaravelのコントローラーへPOST
            const response = await axiosClient.post<LoginResponse>('/login', { email, password });
            // Contextのloginを呼ぶ
            login(response.data.access_token);
            // ログイン成功！記事一覧へ移動
            navigate('/');

        } catch (error: any) {
            // バリデーションエラーや認証失敗のハンドリング
            if (error.response?.status === 422) {
                alert('メールアドレスまたはパスワードの形式が正しくありません');
            } else if (error.response?.status === 401) {
                alert('ログイン情報が正しくありません');
            } else {
                alert('通信エラーが発生しました');
            }
            console.error(error);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-100">
            <div className="w-full max-w-md rounded-xl bg-white p-8 shadow-lg">
                <h2 className="mb-6 text-center text-2xl font-bold text-gray-800">ログイン</h2>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label htmlFor="email" className="block text-sm font-medium text-gray-700">Email</label>
                        <input 
                            type="email" 
                            id="email"
                            value={email} // 現在の値を表示
                            onChange={(e) => setEmail(e.target.value)} // 入力されたら状態を更新
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 text-gray-900" 
                            required
                        />
                    </div>
                    <div>
                        <label htmlFor="password" className="block text-sm font-medium text-gray-700">Password</label>
                        <input 
                            type="password" 
                            id="password"
                            value={password} // 現在の値を表示
                            onChange={(e) => setPassword(e.target.value)} // 入力されたら状態を更新
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 text-gray-900" 
                            required
                        />
                    </div>
                    <button type="submit" className="w-full rounded-md bg-sky-600 py-2 text-white hover:bg-sky-700 transition-colors">
                        サインイン
                    </button>
                </form>
            </div>
        </div>
    );
};

export default LoginPage;