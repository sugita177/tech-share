import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import ArticleDetailPage from './ArticleDetailPage';
import axiosClient from '../api/axiosClient';

// axios のモック
vi.mock('../api/axiosClient');

// navigate のモック
const mockNavigate = vi.fn();
vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useNavigate: () => mockNavigate,
    };
});

describe('ArticleDetailPage', () => {
    const mockArticle = {
        id: 1,
        title: 'テスト記事タイトル',
        content: 'テスト記事の本文です。',
        slug: 'test-slug',
        created_at: '2024-01-01T00:00:00Z',
    };

    beforeEach(() => {
        vi.clearAllMocks();
        // window.alert は jsdom にないためモック化
        window.alert = vi.fn();
    });

    const renderComponent = (slug = 'test-slug') => {
        return render(
            <MemoryRouter initialEntries={[`/articles/${slug}`]}>
                <Routes>
                    <Route path="/articles/:slug" element={<ArticleDetailPage />} />
                    <Route path="/" element={<div>Dashboard</div>} />
                </Routes>
            </MemoryRouter>
        );
    };

    it('記事データが正常に取得され、画面に表示されること', async () => {
        (axiosClient.get as any).mockResolvedValue({
            data: { data: mockArticle }
        });

        renderComponent();

        expect(screen.getByText(/読み込み中/i)).toBeInTheDocument();

        await waitFor(() => {
            expect(screen.getByText('テスト記事タイトル')).toBeInTheDocument();
            expect(screen.getByText('テスト記事の本文です。')).toBeInTheDocument();
            // 日付の表示（ロケールによって変わる可能性があるため、部分一致などで調整）
            expect(screen.getByText(/2024年1月1日/)).toBeInTheDocument();
        });

        // 正しいスラグでAPIが呼ばれたか
        expect(axiosClient.get).toHaveBeenCalledWith('/articles/test-slug');
    });

    it('記事が見つからない場合、アラートを表示してダッシュボードにリダイレクトされること', async () => {
        // APIエラー（404など）をシミュレート
        (axiosClient.get as any).mockRejectedValue(new Error('Not Found'));

        renderComponent('non-existent-slug');

        await waitFor(() => {
            expect(window.alert).toHaveBeenCalledWith('記事が見つかりませんでした。');
            expect(mockNavigate).toHaveBeenCalledWith('/');
        });
    });

    it('「戻る」ボタンが正しいリンク先を持っていること', async () => {
        (axiosClient.get as any).mockResolvedValue({
            data: { data: mockArticle }
        });

        renderComponent();

        await waitFor(() => {
            const backLink = screen.getByRole('link', { name: /記事一覧に戻る/i });
            expect(backLink).toHaveAttribute('href', '/');
        });
    });

    it('削除ボタンを押して確認ダイアログでOKを押すと、削除APIが呼ばれ一覧へ遷移すること', async () => {
        (axiosClient.get as any).mockResolvedValue({ data: { data: mockArticle } });
        (axiosClient.delete as any).mockResolvedValue({}); // 204想定なので空でOK

        // confirmをモック化して true を返すように設定
        const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

        renderComponent();
        await screen.findByText('テスト記事タイトル');

        const deleteButton = screen.getByRole('button', { name: /削除/i });
        fireEvent.click(deleteButton);

        // 確認ダイアログが出たか
        expect(confirmSpy).toHaveBeenCalled();

        await waitFor(() => {
            // 正しいIDでDELETEリクエストが飛んだか
            expect(axiosClient.delete).toHaveBeenCalledWith(`/articles/${mockArticle.id}`);
            // 削除後にトップへ戻ったか
            expect(mockNavigate).toHaveBeenCalledWith('/');
        });

        confirmSpy.mockRestore();
    });
});