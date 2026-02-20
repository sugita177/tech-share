<?php

use App\Domain\Entities\Article as ArticleEntity;
use App\Domain\Enums\ArticleStatus;
use App\Infrastructure\Persistence\EloquentArticleRepository;
use App\Models\User;
use App\Models\Article as EloquentArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @property EloquentArticleRepository $repository
 */
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
        status: ArticleStatus::Published
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
        'status'  => ArticleStatus::Published
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

test('paginate: 指定したステータスの記事のみが取得されること', function () {
    // 1. 準備：公開記事と草稿を混ぜて作成
    $user = User::factory()->create();
    
    EloquentArticle::factory()->create([
        'user_id' => $user->id,
        'title' => '公開記事だよ',
        'status' => ArticleStatus::Published
    ]);
    
    EloquentArticle::factory()->create([
        'user_id' => $user->id,
        'title' => 'まだ下書きだよ',
        'status' => ArticleStatus::Draft
    ]);

    // 2. 実行：ステータス「Published」を指定して取得
    $result = $this->repository->paginate(10, ArticleStatus::Published);

    // 3. 検証
    // 取得できたのは1件だけであること
    expect($result->items())->toHaveCount(1);
    // その1件は「公開記事」であること
    expect($result->items()[0]->title)->toBe('公開記事だよ');
    // ステータスがPublishedであること
    expect($result->items()[0]->status)->toBe(ArticleStatus::Published);
});

test('paginate: 下書きを指定した場合、公開記事が含まれないこと', function () {
    // 1. 準備
    $user = User::factory()->create();
    EloquentArticle::factory()->create(['status' => ArticleStatus::Published, 'user_id' => $user->id]);
    EloquentArticle::factory()->create(['status' => ArticleStatus::Draft, 'user_id' => $user->id]);

    // 2. 実行
    $result = $this->repository->paginate(10, ArticleStatus::Draft);

    // 3. 検証
    expect($result->items())->toHaveCount(1);
    expect($result->items()[0]->status)->toBe(ArticleStatus::Draft);
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

test('update: 既存の記事を正しく更新できること', function () {
    $user = User::factory()->create();
    $article = EloquentArticle::factory()->create([
        'user_id' => $user->id,
        'title' => '古いタイトル'
    ]);

    $newEntity = new ArticleEntity(
        id: $article->id,
        userId: $user->id,
        title: '新しいタイトル',
        slug: $article->slug,
        content: '新しい内容',
        status: ArticleStatus::Published
    );

    $result = $this->repository->update($newEntity);

    expect($result->title)->toBe('新しいタイトル');
    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
        'title' => '新しいタイトル'
    ]);
});

test('update: 存在しないIDの記事を更新しようとするとModelNotFoundExceptionを投げること', function () {
    // 存在しないID (999など) でEntityを作成
    $entity = new ArticleEntity(
        id: 999,
        userId: 1,
        title: 'タイトル',
        slug: 'slug',
        content: '内容',
        status: ArticleStatus::Published
    );

    expect(fn() => $this->repository->update($entity))
        ->toThrow(ModelNotFoundException::class);
});

test('delete: 指定したIDの記事を物理削除できること', function () {
    // 1. 準備
    $user = User::factory()->create();
    $article = EloquentArticle::factory()->create(['user_id' => $user->id]);

    // 2. 実行
    $this->repository->delete($article->id);

    // 3. 検証
    $this->assertDatabaseMissing('articles', ['id' => $article->id]);
});

test('delete: 存在しないIDを指定した場合、ModelNotFoundExceptionを投げること', function () {
    // 1. 実行 & 2. 検証
    expect(fn() => $this->repository->delete(9999))
        ->toThrow(ModelNotFoundException::class);
});

test('paginate: 特定のユーザーIDで絞り込みができること', function () {
    // 1. 準備：ユーザーAとユーザーBの記事を作成
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    EloquentArticle::factory()->create(['user_id' => $userA->id, 'title' => 'User A Article']);
    EloquentArticle::factory()->create(['user_id' => $userB->id, 'title' => 'User B Article']);

    // 2. 実行：ユーザーAのIDを指定して取得
    $result = $this->repository->paginate(perPage: 10, userId: $userA->id);

    // 3. 検証
    expect($result->items())->toHaveCount(1);
    expect($result->items()[0]->title)->toBe('User A Article');
    expect($result->items()[0]->userId)->toBe($userA->id);
});

test('paginate: ユーザーIDとステータスの組み合わせで絞り込みができること', function () {
    $user = User::factory()->create();
    
    // 公開中と下書きを1件ずつ作成
    EloquentArticle::factory()->create(['user_id' => $user->id, 'status' => ArticleStatus::Published]);
    EloquentArticle::factory()->create(['user_id' => $user->id, 'status' => ArticleStatus::Draft]);

    // 実行：ユーザーID + 下書きステータスで取得
    $result = $this->repository->paginate(perPage: 10, status: ArticleStatus::Draft, userId: $user->id);

    expect($result->items())->toHaveCount(1);
    expect($result->items()[0]->status)->toBe(ArticleStatus::Draft);
});