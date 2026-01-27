import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import ArticleEditPage from './ArticleEditPage';
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

describe('ArticleEditPage', () => {
    const mockArticle = {
        id: 10,
        title: '元のタイトル',
        content: '元の本文',
        slug: 'original-slug',
        status: 'draft',
    };

    beforeEach(() => {
        vi.clearAllMocks();
        window.alert = vi.fn();
    });

    const renderComponent = (slug = 'original-slug') => {
        return render(
            <MemoryRouter initialEntries={[`/articles/${slug}/edit`]}>
                <Routes>
                    <Route path="/articles/:slug/edit" element={<ArticleEditPage />} />
                </Routes>
            </MemoryRouter>
        );
    };

    it('初期表示で既存の記事データがフォームにセットされること', async () => {
        (axiosClient.get as any).mockResolvedValue({ data: { data: mockArticle } });

        renderComponent();

        // 読み込み完了を待つ
        await waitFor(() => {
            expect(screen.getByDisplayValue('元のタイトル')).toBeInTheDocument();
            expect(screen.getByDisplayValue('元の本文')).toBeInTheDocument();
            expect(screen.getByDisplayValue('original-slug')).toBeInTheDocument();
            expect(screen.getByDisplayValue('下書き保存')).toBeInTheDocument();
        });
    });

    it('フォームを入力して更新ボタンを押すと、正しいデータでAPIが呼ばれること', async () => {
        (axiosClient.get as any).mockResolvedValue({ data: { data: mockArticle } });
        (axiosClient.put as any).mockResolvedValue({ data: {} });

        renderComponent();

        // データの読み込みを待つ
        await screen.findByDisplayValue('元のタイトル');

        // 値を書き換える
        fireEvent.change(screen.getByLabelText(/タイトル/i), { target: { value: '更新後のタイトル' } });
        fireEvent.change(screen.getByLabelText(/スラグ/i), { target: { value: 'updated-slug' } });
        fireEvent.change(screen.getByLabelText(/本文/i), { target: { value: '更新後の本文内容' } });
        fireEvent.change(screen.getByLabelText(/公開ステータス/i), { target: { value: 'published' } });

        // 送信
        fireEvent.click(screen.getByRole('button', { name: /更新を保存する/i }));

        await waitFor(() => {
            // axios.put(URL, payload) の検証
            // IDはmockArticleの10が使われるはず
            expect(axiosClient.put).toHaveBeenCalledWith('/articles/10', {
                title: '更新後のタイトル',
                content: '更新後の本文内容',
                slug: 'updated-slug',
                status: 'published'
            });

            expect(window.alert).toHaveBeenCalledWith('記事を更新しました');
            // 更新後の新しいスラグの詳細画面へ遷移するか
            expect(mockNavigate).toHaveBeenCalledWith('/articles/updated-slug');
        });
    });

    it('APIエラー時にアラートが表示されること', async () => {
        (axiosClient.get as any).mockResolvedValue({ data: { data: mockArticle } });
        (axiosClient.put as any).mockRejectedValue({
            response: { data: { message: 'バリデーションエラーが発生しました' } }
        });

        renderComponent();
        await screen.findByDisplayValue('元のタイトル');

        fireEvent.click(screen.getByRole('button', { name: /更新を保存する/i }));

        await waitFor(() => {
            expect(window.alert).toHaveBeenCalledWith('バリデーションエラーが発生しました');
        });
    });

    it('キャンセルボタンを押すと前の画面に戻ること', async () => {
        (axiosClient.get as any).mockResolvedValue({ data: { data: mockArticle } });

        renderComponent();
        await screen.findByDisplayValue('元のタイトル');

        fireEvent.click(screen.getByRole('button', { name: /キャンセルして戻る/i }));

        expect(mockNavigate).toHaveBeenCalledWith(-1);
    });
});