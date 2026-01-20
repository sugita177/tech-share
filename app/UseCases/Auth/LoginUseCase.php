<?php

namespace App\UseCases\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUseCase
{
    /**
     * @return string 発行されたアクセストークン
     */
    public function execute(string $email, string $password): string
    {
        $user = User::where('email', $email)->first();

        // ユーザーが存在しない、またはパスワードが一致しない場合
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が正しくありません。'],
            ]);
        }

        // Sanctumのトークンを発行（名前は何でも良いですが 'auth_token' とします）
        return $user->createToken('auth_token')->plainTextToken;
    }
}