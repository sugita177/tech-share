import axios from 'axios';

const axiosClient = axios.create({
    // VITE_API_BASE_URL を読み込む。未設定なら '/api' を使う
    baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
});

// ログイン状態が切れた（401）時の処理だけ残す
axiosClient.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // クッキー方式ではトークン削除は不要ですが、
            // 必要に応じて画面をリロードさせたり、ログイン画面へ飛ばします
            // window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default axiosClient;