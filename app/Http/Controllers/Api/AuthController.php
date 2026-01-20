<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\UseCases\Auth\LoginUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(Request $request, LoginUseCase $useCase): JsonResponse
    {
        // 基本的なバリデーション
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $token = $useCase->execute($credentials['email'], $credentials['password']);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
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