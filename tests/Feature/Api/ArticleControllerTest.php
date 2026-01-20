<?php

use App\Models\User;
use App\Models\Article as EloquentArticle;
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
    $user = User::factory()->create();
    // 15件作成
    EloquentArticle::factory()->count(15)->create(['user_id' => $user->id]);

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

test('記事詳細をスラグで取得できること', function () {
    $user = User::factory()->create();
    $article = EloquentArticle::factory()->create([
        'user_id' => $user->id,
        'slug' => 'test-slug'
    ]);

    $response = $this->getJson("/api/articles/test-slug");

    $response->assertStatus(200)
             ->assertJsonPath('data.slug', 'test-slug')
             ->assertJsonPath('data.title', $article->title);
});

test('存在しないスラグを指定した場合、404が返ること', function () {
    $response = $this->getJson("/api/articles/non-existent-slug");

    $response->assertStatus(404);
});

test('記事を更新できること（スラグ変更なし）', function () {
    $user = \App\Models\User::factory()->create();
    $article = \App\Models\Article::factory()->create([
        'user_id' => $user->id,
        'title' => '元々のタイトル',
        'slug' => 'original-slug'
    ]);

    $payload = [
        'title' => '更新後のタイトル',
        'content' => '更新後の本文',
        'slug' => 'original-slug', // 同じスラグ
        'status' => 'published'
    ];

    $response = $this->putJson("/api/articles/{$article->id}", $payload);

    $response->assertStatus(200);
    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
        'title' => '更新後のタイトル',
        'slug' => 'original-slug'
    ]);
});

test('他の記事が使用中のスラグには更新できないこと', function () {
    $user = User::factory()->create();
    EloquentArticle::factory()->create(['slug' => 'taken-slug']);
    $article = EloquentArticle::factory()->create(['slug' => 'my-slug']);

    $payload = [
        'title' => 'タイトル',
        'content' => '本文',
        'slug' => 'taken-slug', // 重複！
        'status' => 'published'
    ];

    $response = $this->putJson("/api/articles/{$article->id}", $payload);

    // 検証：422エラーが返り、slugに関するエラーメッセージが含まれていること
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['slug']);
});

test('記事を削除できること', function () {
    $user = User::factory()->create();
    $article = EloquentArticle::factory()->create(['user_id' => $user->id]);

    $response = $this->deleteJson("/api/articles/{$article->id}");

    $response->assertStatus(204);
    // DBから消えていることを確認
    $this->assertDatabaseMissing('articles', ['id' => $article->id]);
});

test('存在しない記事を削除しようとすると404が返ること', function () {
    $response = $this->deleteJson("/api/articles/9999");

    $response->assertStatus(404);
});