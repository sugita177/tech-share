import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import ArticleCreatePage from './ArticleCreatePage';
import axiosClient from '../api/axiosClient';

// モック設定
vi.mock('../api/axiosClient');
const mockedNavigate = vi.fn();
vi.mock('react-router-dom', async () => ({
    ...(await vi.importActual('react-router-dom')),
    useNavigate: () => mockedNavigate,
}));

describe('ArticleCreatePage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        window.alert = vi.fn(); // alertをモック化
    });

    const renderPage = () => {
        render(
            <BrowserRouter>
                <ArticleCreatePage />
            </BrowserRouter>
        );
    };

    it('フォームが正しくレンダリングされ、入力ができること', () => {
        renderPage();
        
        const titleInput = screen.getByLabelText(/タイトル/i) as HTMLInputElement;
        const contentInput = screen.getByLabelText(/本文/i) as HTMLTextAreaElement;

        fireEvent.change(titleInput, { target: { value: 'テストタイトル' } });
        fireEvent.change(contentInput, { target: { value: 'テスト本文の内容です' } });

        expect(titleInput.value).toBe('テストタイトル');
        expect(contentInput.value).toBe('テスト本文の内容です');
    });

    it('投稿に成功したとき、アラートを表示してトップへ遷移すること', async () => {
        (axiosClient.post as any).mockResolvedValue({ data: {} });
        renderPage();

        fireEvent.change(screen.getByLabelText(/タイトル/i), { target: { value: '新記事' } });
        fireEvent.change(screen.getByLabelText(/本文/i), { target: { value: '本文' } });
        
        fireEvent.click(screen.getByRole('button', { name: /公開する/i }));

        await waitFor(() => {
            // 正しいエンドポイントとデータで叩かれているか
            expect(axiosClient.post).toHaveBeenCalledWith('/articles', {
                title: '新記事',
                content: '本文'
            });
            // アラートが出ているか
            expect(window.alert).toHaveBeenCalledWith('記事を投稿しました！');
            // 画面遷移しているか
            expect(mockedNavigate).toHaveBeenCalledWith('/');
        });
    });

    it('投稿中はボタンが「投稿中...」に変わり、連打できないこと', async () => {
        // 意図的にレスポンスを遅延させる
        (axiosClient.post as any).mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)));
        renderPage();

        fireEvent.change(screen.getByLabelText(/タイトル/i), { target: { value: '連打テスト' } });
        fireEvent.change(screen.getByLabelText(/本文/i), { target: { value: '内容' } });

        const submitButton = screen.getByRole('button', { name: /公開する/i });
        fireEvent.click(submitButton);

        // 投稿中の状態を確認
        expect(submitButton).toBeDisabled();
        expect(submitButton).toHaveTextContent('投稿中...');
    });

    it('キャンセルボタンを押すと前の画面に戻ること', () => {
        renderPage();
        const cancelButton = screen.getByRole('button', { name: /キャンセル/i });
        fireEvent.click(cancelButton);

        // navigate(-1) が呼ばれているか
        expect(mockedNavigate).toHaveBeenCalledWith(-1);
    });
});