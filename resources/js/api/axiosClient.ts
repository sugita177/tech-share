import axios from 'axios';

const axiosClient = axios.create({
    baseURL: '/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
});

// リクエスト送信前に実行される「インターセプター」
axiosClient.interceptors.request.use((config) => {
    // ローカルストレージからトークンを取得してヘッダーにセット
    const token = localStorage.getItem('access_token');
    if (token && config.headers) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// レスポンス受信時に実行される「インターセプター」
axiosClient.interceptors.response.use(
    (response) => response,
    (error) => {
        // 401 (認証エラー) が返ってきたら、トークンを消してログイン画面へ
        if (error.response?.status === 401) {
            localStorage.removeItem('access_token');
            // 必要に応じて window.location.href = '/login' など
        }
        return Promise.reject(error);
    }
);

export default axiosClient;