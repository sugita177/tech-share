// 認証レスポンス
export interface LoginResponse {
    access_token: string;
    token_type: string;
}

export interface Article {
    id: number;
    title: string;
    content: string;
    created_at: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
}

// ページ情報の型定義
export interface PaginationData {
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}