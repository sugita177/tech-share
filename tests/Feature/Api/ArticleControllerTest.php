<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class); // テストごとにDBをリセット

test('API経由で記事を作成できる', function () {
    $this->withoutExceptionHandling(); // これを追加して実行すると、詳細なエラーが出ます

    // テスト用ユーザーを作成
    $user = User::factory()->create();

    // 1. 準備
    $payload = [
        'title'   => 'APIテスト記事',
        'content' => 'APIからの投稿テストです',
        'status'  => 'published',
        'user_id' => $user->id,
    ];

    // 2. 実行（HTTP POSTリクエストを送信）
    $response = $this->actingAs($user)
        ->postJson('/api/articles', $payload);

    // 3. 検証
    $response->assertStatus(201)
             ->assertJsonPath('data.title', 'APIテスト記事');

    // データベースに保存されているか確認
    $this->assertDatabaseHas('articles', [
        'title' => 'APIテスト記事',
        'user_id' => $user->id,
    ]);
});

test('タイトルがない場合はバリデーションエラーになる', function () {
    $response = $this->postJson('/api/articles', [
        'content' => 'タイトルがありません'
    ]);

    $response->assertStatus(422) // Unprocessable Entity
             ->assertJsonValidationErrors(['title']);
});

test('記事一覧がページネーション形式で取得できること', function () {
    $user = \App\Models\User::factory()->create();
    // 15件作成
    \App\Models\Article::factory()->count(15)->create(['user_id' => $user->id]);

    $response = $this->getJson('/api/articles');

    $response->assertStatus(200)
             ->assertJsonCount(10, 'data') // 1ページ目は10件
             ->assertJsonStructure([
                 'data',
                 'links' => ['first', 'last', 'prev', 'next'],
                 'meta'  => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total']
             ]);

    expect($response->json('meta.total'))->toBe(15);
});