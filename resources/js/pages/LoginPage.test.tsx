import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import LoginPage from './LoginPage';
import axiosClient from '../api/axiosClient';
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
    
    const renderLoginPage = (isAuthenticated = false) => {
        return render(
            <AuthContext.Provider value={{ 
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
        // window.alert のモック
        window.alert = vi.fn();
    });

    it('フォームが正しくレンダリングされること', () => {
        renderLoginPage();
        expect(screen.getByRole('textbox', {name: /email/i})).toBeInTheDocument();
        expect(screen.getByLabelText(/Password/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /サインイン/i })).toBeInTheDocument();
    });

    it('正しい情報を入力して送信するとログインに成功し、トップへ遷移すること', async () => {
        const mockResponse = { data: { access_token: 'fake-token' } };
        (axiosClient.post as any).mockResolvedValue(mockResponse);

        renderLoginPage();

        fireEvent.change(screen.getByRole('textbox', {name: /email/i}), { target: { value: 'test@example.com' } });
        fireEvent.change(screen.getByLabelText(/Password/i), { target: { value: 'password123' } });
        fireEvent.click(screen.getByRole('button', { name: /サインイン/i }));

        await waitFor(() => {
            expect(axiosClient.post).toHaveBeenCalledWith('/login', {
                email: 'test@example.com',
                password: 'password123',
            });
            expect(mockLogin).toHaveBeenCalledWith('fake-token');
            expect(mockedNavigate).toHaveBeenCalledWith('/');
        });
    });

    it('認証エラー(401)のとき、適切なアラートが表示されること', async () => {
        (axiosClient.post as any).mockRejectedValue({
            response: { status: 401 }
        });

        renderLoginPage();
        fireEvent.change(screen.getByRole('textbox', {name: /email/i}), { target: { value: 'wrong@example.com' } });
        fireEvent.change(screen.getByLabelText(/password/i), { target: { value: 'wrong-password' } });
        fireEvent.click(screen.getByRole('button', { name: /サインイン/i }));

        await waitFor(() => {
            expect(window.alert).toHaveBeenCalledWith('ログイン情報が正しくありません');
        });
    });
});