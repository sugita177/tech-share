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
        $request->session()->regenerate();

        // トークンを返さず、成功ステータスのみを返す
        return response()->noContent(); 
    }

    // ログアウト機能（現在のトークンを削除）
    public function logout(Request $request): JsonResponse
    {
        // 現在のトークンを取得
        $token = $request->user()->currentAccessToken();
    
        // トークンが PersonalAccessToken (DB保存) の場合のみ削除を実行
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }
    
        return response()->json(['message' => 'Logged out']);
    }
}