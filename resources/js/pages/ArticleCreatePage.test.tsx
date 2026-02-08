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

    it('フォームが正しくレンダリングされ、初期状態が「下書き」であること', () => {
        renderPage();
        
        const titleInput = screen.getByLabelText(/タイトル/i) as HTMLInputElement;
        const contentInput = screen.getByLabelText(/本文/i) as HTMLTextAreaElement;

        fireEvent.change(titleInput, { target: { value: 'テストタイトル' } });
        fireEvent.change(contentInput, { target: { value: 'テスト本文の内容です' } });

        expect(titleInput.value).toBe('テストタイトル');
        expect(contentInput.value).toBe('テスト本文の内容です');

        // ラジオボタンの初期状態チェック
        const draftRadio = screen.getByLabelText(/下書き保存/i) as HTMLInputElement;
        const publishRadio = screen.getByLabelText(/公開する/i) as HTMLInputElement;
        
        expect(draftRadio.checked).toBe(true);      // デフォルトは draft
        expect(publishRadio.checked).toBe(false);

        // ボタンの文言チェック（動的に変わるため重要）
        expect(screen.getByRole('button', { name: /下書き保存する/i })).toBeInTheDocument();
    });

    it('「公開する」を選択すると、送信ボタンの文言が変わること', () => {
        renderPage();

        // 公開ラジオボタンをクリック
        fireEvent.click(screen.getByLabelText(/公開する/i));

        // 送信ボタンが「公開する」に変わっているか
        expect(screen.getByRole('button', { name: /公開する/i })).toBeInTheDocument();
        // 「下書き保存する」ボタンは消えているか
        expect(screen.queryByRole('button', { name: /下書き保存する/i })).toBeNull();
    });

    it('デフォルト（下書き）のまま投稿したとき、status: draft で送信されること', async () => {
        (axiosClient.post as any).mockResolvedValue({ data: {} });
        renderPage();

        fireEvent.change(screen.getByLabelText(/タイトル/i), { target: { value: '下書き記事' } });
        fireEvent.change(screen.getByLabelText(/本文/i), { target: { value: 'ドラフト' } });
        
        // デフォルトは「下書き保存する」ボタン
        fireEvent.click(screen.getByRole('button', { name: /下書き保存する/i }));

        await waitFor(() => {
            // ★修正: status: 'draft' が含まれていることを確認
            expect(axiosClient.post).toHaveBeenCalledWith('/articles', {
                title: '下書き記事',
                content: 'ドラフト',
                status: 'draft' // ここが重要
            });
            expect(window.alert).toHaveBeenCalledWith('記事を下書き保存しました！');
            expect(mockedNavigate).toHaveBeenCalledWith('/');
        });
    });

    it('「公開する」を選択して投稿したとき、status: published で送信されること', async () => {
        (axiosClient.post as any).mockResolvedValue({ data: {} });
        renderPage();

        fireEvent.change(screen.getByLabelText(/タイトル/i), { target: { value: '公開記事' } });
        fireEvent.change(screen.getByLabelText(/本文/i), { target: { value: '本番' } });

        // 公開に切り替え
        fireEvent.click(screen.getByLabelText(/公開する/i));
        
        // 「公開する」ボタンをクリック
        fireEvent.click(screen.getByRole('button', { name: /公開する/i }));

        await waitFor(() => {
            // ★検証: status: 'published' になっているか
            expect(axiosClient.post).toHaveBeenCalledWith('/articles', {
                title: '公開記事',
                content: '本番',
                status: 'published'
            });
            expect(window.alert).toHaveBeenCalledWith('記事を公開しました！');
            expect(mockedNavigate).toHaveBeenCalledWith('/');
        });
    });

    it('APIエラー時にアラートを表示し、画面遷移しないこと', async () => {
        // エラーを返すようにモック
        (axiosClient.post as any).mockRejectedValue(new Error('Network Error'));
        renderPage();

        fireEvent.change(screen.getByLabelText(/タイトル/i), { target: { value: 'エラーテスト' } });
        fireEvent.change(screen.getByLabelText(/本文/i), { target: { value: 'エラー' } });

        fireEvent.click(screen.getByRole('button', { name: /下書き保存する/i }));

        await waitFor(() => {
            expect(window.alert).toHaveBeenCalledWith('投稿に失敗しました。入力内容を確認してください。');
            // 失敗時は遷移していないことを確認
            expect(mockedNavigate).not.toHaveBeenCalled();
        });
    });

    it('投稿中はボタンが「投稿中...」に変わり、連打できないこと', async () => {
        // 意図的にレスポンスを遅延させる
        (axiosClient.post as any).mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)));
        renderPage();

        fireEvent.change(screen.getByLabelText(/タイトル/i), { target: { value: '連打テスト' } });
        fireEvent.change(screen.getByLabelText(/本文/i), { target: { value: '内容' } });

        const submitButton = screen.getByRole('button', { name: /下書き保存する/i });
        fireEvent.click(submitButton);

        // 投稿中の状態を確認
        expect(submitButton).toBeDisabled();
        expect(submitButton).toHaveTextContent('送信中...');
    });

    it('キャンセルボタンを押すと前の画面に戻ること', () => {
        renderPage();
        const cancelButton = screen.getByRole('button', { name: /キャンセル/i });
        fireEvent.click(cancelButton);

        // navigate(-1) が呼ばれているか
        expect(mockedNavigate).toHaveBeenCalledWith(-1);
    });
});