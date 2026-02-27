<?php

namespace Tests\Feature\Api\My;

use App\Models\User;
use App\Models\Article;
use App\Domain\Enums\ArticleStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('GET /api/my/articles: 認証されていない場合は 401 が返ること', function () {
    $response = $this->getJson('/api/my/articles');
    $response->assertStatus(401);
});

test('GET /api/my/articles: 自分の記事のみが取得され、他人の記事は含まれないこと', function () {
    // 1. 準備
    $me = User::factory()->create();
    $other = User::factory()->create();

    // 自分の記事（公開・下書き）
    Article::factory()->create(['user_id' => $me->id, 'title' => 'My Public', 'status' => ArticleStatus::Published]);
    Article::factory()->create(['user_id' => $me->id, 'title' => 'My Draft', 'status' => ArticleStatus::Draft]);
    
    // 他人の記事
    Article::factory()->create(['user_id' => $other->id, 'title' => 'Other Person Article']);

    // 2. 実行
    $response = $this->actingAs($me)->getJson('/api/my/articles');

    // 3. 検証
    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['title' => 'My Public'])
        ->assertJsonFragment(['title' => 'My Draft'])
        ->assertJsonMissing(['title' => 'Other Person Article']);
});

test('GET /api/my/articles: ステータスを指定して絞り込めること', function () {
    $me = User::factory()->create();
    Article::factory()->create(['user_id' => $me->id, 'status' => ArticleStatus::Published, 'title' => 'Published One']);
    Article::factory()->create(['user_id' => $me->id, 'status' => ArticleStatus::Draft, 'title' => 'Draft One']);

    // 下書き(draft)のみをリクエスト
    $response = $this->actingAs($me)->getJson('/api/my/articles?status=draft');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Draft One')
        ->assertJsonPath('data.0.status', 'draft');
});