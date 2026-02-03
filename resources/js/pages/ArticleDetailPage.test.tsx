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

// useAuth のモック
const mockUseAuth = vi.fn();
vi.mock('../contexts/AuthContext', () => ({
    useAuth: () => mockUseAuth(),
}));

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

const mockArticle = {
    id: 1,
    user_id: 1,
    title: 'テスト記事タイトル',
    content: 'テスト記事の本文です。',
    slug: 'test-slug',
    created_at: '2024-01-01T00:00:00Z',
};

describe('ArticleDetailPage', () => {

    beforeEach(() => {
        vi.clearAllMocks();
        // window.alert は jsdom にないためモック化
        window.alert = vi.fn();

        mockUseAuth.mockReturnValue({ 
            user: { id: 1, name: 'Test User' },
            loading: false 
        });
    });

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

        // ログインユーザーのIDも記事の作成者のidと同じ1にする
        mockUseAuth.mockReturnValue({ 
            user: { id: 1 }, 
            loading: false 
        });

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

describe('権限による表示制御', () => {
    const mockArticleWithOwner = {
        ...mockArticle,
        user_id: 10, // 記事の所有者IDを10とする
    };

    beforeEach(() => {
        vi.clearAllMocks();
        (axiosClient.get as any).mockResolvedValue({
            data: { data: mockArticleWithOwner }
        });
    });

    it('記事の所有者の場合、編集・削除ボタンが表示されること', async () => {
        // ログインユーザーIDを記事の所有者ID(10)と一致させる
        mockUseAuth.mockReturnValue({ user: { id: 10, is_admin: false } });
        renderComponent();
        await waitFor(() => {
            expect(screen.getByRole('button', { name: /編集する/i })).toBeInTheDocument();
            expect(screen.getByRole('button', { name: /削除/i })).toBeInTheDocument();
        });
    });

    it('記事の所有者でない場合、編集・削除ボタンが表示されないこと', async () => {
        // ログインユーザーIDを記事の所有者ID(10)と異なるものにする
        mockUseAuth.mockReturnValue({ user: { id: 999, is_admin: false } });
        renderComponent();
        await waitFor(() => {
            // queryByRole を使うことで、存在しない場合にエラーにならず null を返す
            expect(screen.queryByRole('button', { name: /編集する/i })).not.toBeInTheDocument();
            expect(screen.queryByRole('button', { name: /削除/i })).not.toBeInTheDocument();
        });
    });

    it('管理者の場合、他人の記事でも編集・削除ボタンが表示されること', async () => {
        // IDは違うが管理者の場合
        mockUseAuth.mockReturnValue({ user: { id: 999, is_admin: true } });
        renderComponent();
        await waitFor(() => {
            expect(screen.getByRole('button', { name: /編集する/i })).toBeInTheDocument();
        });
    });

    it('未ログインの場合、編集・削除ボタンが表示されないこと', async () => {
        // user が null の場合
        mockUseAuth.mockReturnValue({ user: null });
        renderComponent();
        await waitFor(() => {
            expect(screen.queryByRole('button', { name: /編集する/i })).not.toBeInTheDocument();
        });
    });
});

describe('追加の検証シナリオ', () => {
    const mockArticleOther = {
        ...mockArticle,
        user_id: 99, // 自分以外のID
    };

    beforeEach(() => {
        vi.clearAllMocks();
        (axiosClient.get as any).mockResolvedValue({ data: { data: mockArticleOther } });
    });

    it('管理者が他人の記事を閲覧している場合、「管理者モード」バッジが表示されること', async () => {
        // 管理者で、記事の所有者ではない状態
        mockUseAuth.mockReturnValue({ user: { id: 1, is_admin: true }, loading: false });

        renderComponent();

        await waitFor(() => {
            expect(screen.getByText(/管理者モードで閲覧中/i)).toBeInTheDocument();
        });
    });

    it('削除確認ダイアログでキャンセルを押した場合、削除APIが呼ばれないこと', async () => {
        mockUseAuth.mockReturnValue({ user: { id: 1, is_admin: true }, loading: false });
        
        // confirm で false (キャンセル) を返す
        const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

        renderComponent();
        await screen.findByText('テスト記事タイトル');

        const deleteButton = screen.getByRole('button', { name: /削除/i });
        fireEvent.click(deleteButton);

        expect(confirmSpy).toHaveBeenCalled();
        // APIが呼ばれていないことを検証（フェイルセーフの確認）
        expect(axiosClient.delete).not.toHaveBeenCalled();
        
        confirmSpy.mockRestore();
    });

    it('削除APIが失敗した場合、エラーアラートが表示されること', async () => {
        mockUseAuth.mockReturnValue({ user: { id: 1, is_admin: true }, loading: false });
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        
        // 削除APIの失敗をシミュレート
        const errorMessage = "サーバーエラーが発生しました";
        (axiosClient.delete as any).mockRejectedValue({
            response: { data: { message: errorMessage } }
        });

        renderComponent();
        await screen.findByText('テスト記事タイトル');

        const deleteButton = screen.getByRole('button', { name: /削除/i });
        fireEvent.click(deleteButton);

        await waitFor(() => {
            expect(window.alert).toHaveBeenCalledWith(errorMessage);
            // 失敗時はリダイレクトされないことも確認
            expect(mockNavigate).not.toHaveBeenCalledWith('/');
        });
    });
});