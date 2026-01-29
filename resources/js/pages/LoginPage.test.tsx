import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import LoginPage from './LoginPage';
import { AuthContext } from '../contexts/AuthContext';

// axios と navigate のモック
vi.mock('../api/axiosClient');
const mockedNavigate = vi.fn();
vi.mock('react-router-dom', async () => ({
    ...(await vi.importActual('react-router-dom')),
    useNavigate: () => mockedNavigate,
}));

describe('LoginPage', () => {
    const mockLogin = vi.fn();

    // 共通のレンダー関数
    const renderLoginPage = (isAuthenticated = false) => {
        return render(
            <AuthContext.Provider value={{ 
                user: null, // userが必要な場合は追加
                login: mockLogin, 
                logout: vi.fn(), 
                isAuthenticated, 
                loading: false 
            }}>
                <BrowserRouter>
                    <LoginPage />
                </BrowserRouter>
            </AuthContext.Provider>
        );
    };

    beforeEach(() => {
        vi.clearAllMocks();
        window.alert = vi.fn();
    });

    it('フォームが正しくレンダリングされること', () => {
        renderLoginPage();
        expect(screen.getByRole('textbox', {name: /email/i})).toBeInTheDocument();
        expect(screen.getByLabelText(/Password/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /サインイン/i })).toBeInTheDocument();
    });

    it('正しい情報を入力して送信すると、Contextのloginが呼ばれトップへ遷移すること', async () => {
        mockLogin.mockResolvedValue(undefined); 

        renderLoginPage(false);
        
        fireEvent.change(screen.getByLabelText(/Email/i), { target: { value: 'test@example.com' } });
        fireEvent.change(screen.getByLabelText(/Password/i), { target: { value: 'password123' } });
        
        // submitを発火
        fireEvent.click(screen.getByRole('button', { name: /サインイン/i }));
        
        // loginが呼ばれるのを待つ
        await waitFor(() => {
            expect(mockLogin).toHaveBeenCalled();
        });
    
        // その後、navigateが呼ばれるのを少し長めに待つ
        await waitFor(() => {
            expect(mockedNavigate).toHaveBeenCalledWith('/');
        }, { timeout: 2000 });
    });

    it('認証エラー(401)のとき、適切なアラートが表示されること', async () => {
        // login 関数が 401 エラーを投げるように設定
        mockLogin.mockRejectedValue({
            response: { status: 401 }
        });

        renderLoginPage();
        
        fireEvent.change(screen.getByLabelText(/Email/i), { target: { value: 'wrong@example.com' } });
        fireEvent.change(screen.getByLabelText(/Password/i), { target: { value: 'wrong-password' } });
        fireEvent.click(screen.getByRole('button', { name: /サインイン/i }));

        await waitFor(() => {
            expect(window.alert).toHaveBeenCalledWith('ログイン情報が正しくありません');
        });
    });
});