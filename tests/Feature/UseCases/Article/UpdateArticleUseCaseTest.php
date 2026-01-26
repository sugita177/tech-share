<?php

use App\Domain\Entities\Article;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\UseCases\Article\UpdateArticleUseCase;
use App\UseCases\Article\UpdateArticleInput;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Enums\ArticleStatus;
use Mockery\MockInterface;
use Illuminate\Support\Str;

test('execute: 正しい入力データで記事を更新し、更新後のEntityを返すこと', function () {
    // 1. 準備
    $articleId = 1;
    $userId = 10;
    
    // 現在のデータ（DBにある想定）
    $currentEntity = new Article(
        id: $articleId,
        userId: $userId,
        title: '元のタイトル',
        slug: 'original-slug',
        content: '元の本文',
        status: ArticleStatus::Draft,
        viewCount: 5
    );

    // 更新後の期待データ
    $updatedEntity = new Article(
        id: $articleId,
        userId: $userId,
        title: '新しいタイトル',
        slug: 'new-slug',
        content: '新しい本文',
        status: ArticleStatus::Published,
        viewCount: 5
    );

    /** @var ArticleRepositoryInterface|MockInterface $repository */
    $repository = Mockery::mock(ArticleRepositoryInterface::class);

    // 手順1: 現在のデータをIDで取得
    $repository->shouldReceive('findById')
        ->once()
        ->with($articleId)
        ->andReturn($currentEntity);

    // 手順2: スラグが変更されるため、重複チェックが走る
    $repository->shouldReceive('existsBySlug')
        ->once()
        ->with('new-slug')
        ->andReturn(false);

    // 手順3: リポジトリのupdateが呼ばれる
    $repository->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function (Article $article) use ($articleId, $userId) {
            // userIdやidが書き換わっていないか、入力値が反映されているかを検証
            return $article->id === $articleId &&
                   $article->userId === $userId &&
                   $article->title === '新しいタイトル' &&
                   $article->slug === 'new-slug';
        }))
        ->andReturn($updatedEntity);

    $useCase = new UpdateArticleUseCase($repository);
    
    $input = new UpdateArticleInput(
        id: $articleId,
        userId: $userId,
        title: '新しいタイトル',
        content: '新しい本文',
        slug: 'new-slug',
        status: ArticleStatus::Published
    );

    // 2. 実行
    $result = $useCase->execute($input);

    // 3. 検証
    expect($result)->toBe($updatedEntity);
});

test('execute: 重複したスラグを指定した場合、ValidationExceptionを投げること', function () {
    // 1. 準備
    $currentArticle = new Article(id: 1, userId: 1, title: '旧', slug: 'old-slug', content: '..', status: ArticleStatus::Draft);
    $anotherArticle = new Article(id: 2, userId: 1, title: '他', slug: 'taken-slug', content: '..', status: ArticleStatus::Draft);

    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    $repository->shouldReceive('findById')->with(1)->andReturn($currentArticle);
    // スラグ 'taken-slug' は既に存在すると返す
    $repository->shouldReceive('existsBySlug')->with('taken-slug')->andReturn(true);

    $useCase = new UpdateArticleUseCase($repository);
    $input = new UpdateArticleInput(id: 1, userId: 1, title: '新', content: '..', slug: 'taken-slug', status: ArticleStatus::Published);

    // 2. 実行 & 検証
    expect(fn() => $useCase->execute($input))
        ->toThrow(ValidationException::class);
});

test('execute: 更新対象の記事が存在しない場合、ModelNotFoundExceptionを投げること', function () {
    // 準備：findById が null を返すようにモック
    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    $repository->shouldReceive('findById')->with(999)->andReturn(null);

    $useCase = new UpdateArticleUseCase($repository);
    $input = new UpdateArticleInput(id: 999, userId: 1, title: '新', content: '..', slug: 'new-slug', status: ArticleStatus::Published);

    expect(fn() => $useCase->execute($input))
        ->toThrow(ModelNotFoundException::class);
});

test('execute: 自分以外の記事が使用中のスラグに変更しようとした場合、ValidationExceptionを投げること', function () {
    // 1. 準備
    // 自身のデータ
    $current = new Article(id: 1, userId: 1, title: '旧', slug: 'my-slug', content: '..', status: ArticleStatus::Published);
    
    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    $repository->shouldReceive('findById')->with(1)->andReturn($current);
    
    // 他の人が 'other-slug' を使っている状態をシミュレート
    $repository->shouldReceive('existsBySlug')->with('other-slug')->andReturn(true);

    $useCase = new UpdateArticleUseCase($repository);
    $input = new UpdateArticleInput(id: 1, userId: 1, title: '新', content: '..', slug: 'other-slug', status: ArticleStatus::Published);

    // 2. 実行 & 検証
    expect(fn() => $useCase->execute($input))
        ->toThrow(function (ValidationException $e) {
            expect($e->errors())->toHaveKey('slug');
            expect($e->errors()['slug'][0])->toBe('指定されたスラグは既に使用されています。');
        });
});