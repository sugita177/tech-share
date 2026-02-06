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

test('ログアウトするとセッションが無効化され、認証解除されること', function () {
    // 1. ユーザーを準備
    $user = User::factory()->create();

    // 2. ログアウト API を叩く
    // actingAs('web') と、SPAリクエストであることを示す Referer ヘッダーが重要
    $response = $this->actingAs($user, 'web')
        ->withHeader('Referer', 'http://localhost') // SanctumのSPA判定をパスさせる
        ->postJson('/api/logout');

    // 3. ステータスコードの確認
    $response->assertStatus(200)
             ->assertJson(['message' => 'Logged out']);

    // 4. 認証が解除されている（Guest 状態）ことを確認
    $this->assertGuest('web');

    // テストインスタンス内の「認証済みユーザー」をクリアする
    // 物理的に「観測装置」を一度初期化（Reboot）するイメージです
    $this->refreshApplication();

    // 5. 保護されたルートにアクセスして 401 になることを確認
    // ここでも SPA モードとして振る舞うために Referer を入れるとより確実です
    $secondResponse = $this->withHeader('Referer', 'http://localhost')
                           ->getJson('/api/articles');
                           
    $secondResponse->assertStatus(401);
});