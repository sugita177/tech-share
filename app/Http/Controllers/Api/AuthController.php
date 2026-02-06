<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\UseCases\Auth\LoginUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Auth::attempt でセッションを開始する（クッキーを作成）
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['認証情報が正しくありません。'],
            ]);
        }

        // セッションを再生成（固定セッション攻撃対策）
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        // トークンを返さず、成功ステータスのみを返す
        return response()->noContent(); 
    }

    public function logout(Request $request): JsonResponse
    {
        // 1. Webガード（セッション）からログアウト
        Auth::guard('web')->logout();
    
        // 2. 現在のセッションを無効化（サーバー側のセッションデータを破棄）
        $request->session()->invalidate();
    
        // 3. CSRFトークンを再生成（セッション固定攻撃対策）
        $request->session()->regenerateToken();
    
        return response()->json(['message' => 'Logged out']);
    }
}