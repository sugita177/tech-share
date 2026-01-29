<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('正しい資格情報でログインできること', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    // セッション機能を有効にしてリクエストを送る
    $response = $this->withSession([])->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    // 1. 期待するステータスコードを 204 に変更
    $response->assertStatus(204);

    // 2. トークンの検証は不要になったため削除し、認証状態を確認
    $this->assertAuthenticatedAs($user);
});

test('間違ったパスワードではログインできないこと', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

test('ログアウトするとトークンが無効化されること', function () {
    $user = User::factory()->create();

    // 1. 実際にトークンを発行する
    $token = $user->createToken('test-token')->plainTextToken;

    // 2. 発行したトークンを使ってログアウトAPIを叩く
    $response = $this->withToken($token)
                     ->postJson('/api/logout');

    $response->assertStatus(200);

    // 3. DBからトークンが消えていることを物理的に確認（これが一番確実な証明です）
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id
    ]);

    // 4. アプリケーションの状態をリセットして、古い認証キャッシュを破棄する
    $this->refreshApplication();

    // 5. 無効になったはずのトークンで再度アクセス
    $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                           ->getJson('/api/articles');
    
    $secondResponse->assertStatus(401);
});