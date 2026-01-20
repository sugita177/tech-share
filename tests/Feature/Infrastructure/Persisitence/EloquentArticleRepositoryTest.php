<?php

use App\Domain\Entities\Article as ArticleEntity;
use App\Infrastructure\Persistence\EloquentArticleRepository;
use App\Models\User;
use App\Models\Article as EloquentArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new EloquentArticleRepository();
});

test('save: 正しく保存され、IDが付与されたEntityが返されること', function () {
    // 1. 準備
    $user = User::factory()->create();
    $entity = new ArticleEntity(
        id: null,
        userId: $user->id,
        title: 'リポジトリテスト',
        slug: 'repo-test',
        content: 'テスト内容',
        status: 'published'
    );

    // 2. 実行
    $result = $this->repository->save($entity);

    // 3. 検証
    expect($result->id)->not->toBeNull()
        ->and($result->title)->toBe('リポジトリテスト');

    $this->assertDatabaseHas('articles', [
        'id' => $result->id,
        'title' => 'リポジトリテスト',
        'user_id' => $user->id
    ]);
});

test('existsBySlug: 指定したスラグの存在有無を正しく判定できること', function () {
    // 1. 準備
    $user = User::factory()->create();
    // 直接Eloquentモデルでデータを作る（またはrepositoryのsaveを使う）
    \App\Models\Article::create([
        'user_id' => $user->id,
        'title'   => '既存記事',
        'slug'    => 'existing-slug',
        'content' => '本文',
        'status'  => 'published'
    ]);

    // 2. 実行 & 検証
    // 存在するスラグ
    expect($this->repository->existsBySlug('existing-slug'))->toBeTrue();
    
    // 存在しないスラグ
    expect($this->repository->existsBySlug('non-existent-slug'))->toBeFalse();
});

test('paginate: 指定した件数で分割され、2ページ目が正しく取得できること', function () {
    // 1. 準備：合計15件作成（11件目〜15件目が2ページ目になる想定）
    $user = \App\Models\User::factory()->create();
    \App\Models\Article::factory()->count(15)->create(['user_id' => $user->id]);

    // 2. 実行：1ページ10件として、2ページ目をリクエスト
    // Laravelのパジネーターは request の 'page' パラメータを自動参照するため、
    // テスト内でリクエストをシミュレートします
    request()->merge(['page' => 2]);
    $result = $this->repository->paginate(10);

    // 3. 検証
    expect($result->items())->toHaveCount(5); // 15 - 10 = 5件
    expect($result->currentPage())->toBe(2);
    expect($result->lastPage())->toBe(2);
});

test('paginate: 最新の記事が先頭（降順）で取得されること', function () {
    // 1. 準備：作成時間をずらして2件作成
    $user = User::factory()->create();
    
    $oldArticle = EloquentArticle::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDay(),
        'title' => '古い記事'
    ]);
    $newArticle = EloquentArticle::factory()->create([
        'user_id' => $user->id,
        'created_at' => now(),
        'title' => '新しい記事'
    ]);

    // 2. 実行
    $result = $this->repository->paginate(10);

    // 3. 検証：0番目の要素が新しい方の記事であること
    expect($result->items()[0]->title)->toBe('新しい記事');
    expect($result->items()[1]->title)->toBe('古い記事');
});

test('findBySlug: 指定したスラグの記事をEntityとして取得できること', function () {
    // 1. 準備
    $user = User::factory()->create();
    $article = EloquentArticle::factory()->create([
        'user_id' => $user->id,
        'title'   => '詳細テスト',
        'slug'    => 'detail-slug',
    ]);

    // 2. 実行
    $result = $this->repository->findBySlug('detail-slug');

    // 3. 検証
    expect($result)->toBeInstanceOf(ArticleEntity::class)
        ->and($result->id)->toBe($article->id)
        ->and($result->slug)->toBe('detail-slug')
        ->and($result->title)->toBe('詳細テスト');
});

test('findBySlug: 存在しないスラグを指定した場合、nullが返ること', function () {
    // 1. 実行
    $result = $this->repository->findBySlug('non-existent-slug');

    // 2. 検証
    expect($result)->toBeNull();
});