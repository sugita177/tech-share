import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import axiosClient from '../api/axiosClient';
import axios from 'axios';
import { User } from '../types/api';

interface AuthContextType {
    isAuthenticated: boolean;
    user: User | null;
    login: (credentials: { email: string; password: string }) => Promise<void>;
    logout: () => Promise<void>;
    loading: boolean;
}

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
    const [isAuthenticated, setIsAuthenticated] = useState<boolean>(false);
    const [user, setUser] = useState<User | null>(null);
    const [loading, setLoading] = useState<boolean>(true);

    // ユーザー情報をサーバーから取得する関数
    const fetchUser = async () => {
        try {
            const response = await axiosClient.get('/user'); // Laravelの auth:sanctum ルート
            // response.data.data があればそれを、なければ response.data をセットする
            setUser(response.data.data || response.data);
            setIsAuthenticated(true);
        } catch (error) {
            // トークンが無効な場合などはここに来る
            setIsAuthenticated(false);
            setUser(null);
        }
    };

    useEffect(() => {
        // アプリ起動時にLocalStorageをチェック
        const token = localStorage.getItem('access_token');
        if (token) {
            fetchUser().finally(() => setLoading(false));
        } else {
            setLoading(false);
        }
    }, []);

    const login = async (credentials: { email: string; password: string }) => {
        try {
            // 1. CSRFクッキー取得
            const csrfUrl = import.meta.env.VITE_SANCTUM_CSRF_URL;
            await axios.get(csrfUrl, { withCredentials: true });

            // 2. ログイン実行（ここで credentials = {email, password} を渡す）
            await axiosClient.post('/login', credentials);

            // 3. ユーザー情報取得
            await fetchUser();
        } catch (error) {
            console.error("Login process failed", error);
            throw error; // LoginPage側で catch できるように throw する
        }
    };

    const logout = async () => {
        try {
            // Laravel側のトークンを無効化
            await axiosClient.post('/logout');
        } catch (error) {
            console.error('Logout API failed', error);
        } finally {
            // APIの成功失敗に関わらず、フロント側の情報は必ず消去する
            setIsAuthenticated(false);
        }
    };

    return (
        <AuthContext.Provider value={{ isAuthenticated, user, login, logout, loading }}>
            {!loading ? children : <div className="text-center mt-20">認証確認中...</div>}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (!context) throw new Error('useAuth must be used within an AuthProvider');
    return context;
};