import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import TimelinePage from './TimelinePage';
import axiosClient from '../api/axiosClient';
import { AuthContext } from '../contexts/AuthContext';

// axios のモック
vi.mock('../api/axiosClient');

describe('TimelinePage', () => {
    // window.scrollTo は jsdom に存在しないためモック化
    window.scrollTo = vi.fn();

    const mockArticles = [
        { id: 1, title: '記事1', content: '内容1', slug: 'slug-1', created_at: '2024-01-01' },
        { id: 2, title: '記事2', content: '内容2', slug: 'slug-2', created_at: '2024-01-02' },
    ];

    const mockPaginationResponse = {
        data: {
            data: mockArticles,
            links: { prev: null, next: '/articles?page=2' },
            meta: { current_page: 1, last_page: 5 }
        }
    };

    const renderDashboard = () => {
        return render(
            <AuthContext.Provider value={{ 
                user: null,
                login: vi.fn(), 
                logout: vi.fn(), 
                isAuthenticated: true, 
                loading: false 
            }}>
                <BrowserRouter>
                    <TimelinePage />
                </BrowserRouter>
            </AuthContext.Provider>
        );
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('初期表示で記事一覧が正しく表示されること', async () => {
        (axiosClient.get as any).mockResolvedValue(mockPaginationResponse);

        renderDashboard();

        // 読み込み中が表示される
        expect(screen.getByText(/読み込み中/i)).toBeInTheDocument();

        // 非同期で記事が表示されるのを待つ
        await waitFor(() => {
            expect(screen.getByText('記事1')).toBeInTheDocument();
            expect(screen.getByText('記事2')).toBeInTheDocument();
        });
    });

    it('次へボタンをクリックしたときに fetchArticles が正しいページ番号で呼ばれること', async () => {
        (axiosClient.get as any).mockResolvedValue(mockPaginationResponse);

        renderDashboard();

        // 初回の表示を待つ
        await waitFor(() => expect(screen.getByText('記事1')).toBeInTheDocument());

        // 「次へ」ボタンを取得してクリック
        const nextButton = screen.getByRole('button', { name: /次へ/i });
        fireEvent.click(nextButton);

        await waitFor(() => {
            // 2ページ目をリクエストしているか確認
            expect(axiosClient.get).toHaveBeenCalledWith('/articles?page=2');
            // scrollTo が呼ばれているか確認
            expect(window.scrollTo).toHaveBeenCalled();
        });
    });

    it('ログアウトボタンをクリックすると logout 関数が呼ばれること', async () => {
        const mockLogout = vi.fn();
        (axiosClient.get as any).mockResolvedValue(mockPaginationResponse);

        render(
            <AuthContext.Provider value={{
                user: null,
                login: vi.fn(), 
                logout: mockLogout, 
                isAuthenticated: true, 
                loading: false 
            }}>
                <BrowserRouter>
                    <TimelinePage />
                </BrowserRouter>
            </AuthContext.Provider>
        );

        // 追加：まず記事の読み込み（useEffect）が完了するのを待つ
        await waitFor(() => expect(screen.getByText('記事1')).toBeInTheDocument());

        const logoutButton = screen.getByRole('button', { name: /ログアウト/i });
        fireEvent.click(logoutButton);

        expect(mockLogout).toHaveBeenCalled();
    });

    it('5ページ中3ページ目にいる時、ページネーションに省略記号が表示されないこと（全ページ表示範囲内のため）', async () => {
        // 1, 2, 3, 4, 5 はすべて「1, last, current±2」の範囲に入るため ... は出ないはず
        (axiosClient.get as any).mockResolvedValue({
            data: {
                data: [],
                links: { prev: '?', next: '?' },
                meta: { current_page: 3, last_page: 5 }
            }
        });
    
        renderDashboard();
    
        await waitFor(() => {
            [1, 2, 3, 4, 5].forEach(page => {
                expect(screen.getByRole('button', { name: String(page) })).toBeInTheDocument();
            });
            expect(screen.queryByText('...')).not.toBeInTheDocument();
        });
    });
    
    it('10ページ中5ページ目にいる時、前後が省略記号になること', async () => {
        // 5ページ目にいる場合：
        // 表示対象：1(最初), 10(最後), 3,4,5,6,7(現在の前後2)
        // 省略対象：2, 8, 9
        (axiosClient.get as any).mockResolvedValue({
            data: {
                data: [],
                links: { prev: '?', next: '?' },
                meta: { current_page: 5, last_page: 10 }
            }
        });
    
        renderDashboard();
    
        await waitFor(() => {
            // 表示されるべき数字
            [1, 3, 4, 5, 6, 7, 10].forEach(page => {
                expect(screen.getByRole('button', { name: String(page) })).toBeInTheDocument();
            });
            
            // 省略記号が2箇所に出ているか（getAllByTextを使う）
            const dots = screen.getAllByText('...');
            expect(dots).toHaveLength(2);
        });
    });
    
    it('ページ番号をクリックした時に正しいページがリクエストされること', async () => {
        (axiosClient.get as any).mockResolvedValue({
            data: {
                data: [],
                links: { prev: null, next: '?' },
                meta: { current_page: 1, last_page: 10 }
            }
        });
    
        renderDashboard();
    
        // 10ページ目のボタンをクリック
        const lastPageButton = await screen.findByRole('button', { name: '10' });
        fireEvent.click(lastPageButton);
    
        await waitFor(() => {
            expect(axiosClient.get).toHaveBeenCalledWith('/articles?page=10');
        });
    });

    it('各記事に詳細画面への正しいリンクが設定されていること', async () => {
        (axiosClient.get as any).mockResolvedValue(mockPaginationResponse);
        
        renderDashboard();
        
        // 記事の表示を待つ
        await waitFor(() => {
            expect(screen.getByText('記事1')).toBeInTheDocument();
        });
    
        // 「記事1」というテキストを含むリンク（<a>タグ）を探す
        // getByRole('link') は Link コンポーネントがレンダリングする <a> タグを取得します
        const link1 = screen.getByRole('link', { name: /記事1/i });
        const link2 = screen.getByRole('link', { name: /記事2/i });
    
        // href 属性が正しいか確認（BrowserRouter を使っているので、ルートからのパスで検証）
        expect(link1).toHaveAttribute('href', '/articles/slug-1');
        expect(link2).toHaveAttribute('href', '/articles/slug-2');
    });
});