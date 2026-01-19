<?php

use App\Domain\Entities\Article as ArticleEntity;
use App\Infrastructure\Persistence\EloquentArticleRepository;
use App\Models\User;
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