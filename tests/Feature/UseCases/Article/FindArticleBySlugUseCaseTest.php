<?php

namespace Tests\Unit\UseCases\Article;

use App\UseCases\Article\FindArticleBySlugUseCase;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Entities\Article as ArticleEntity;
use App\Domain\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Mockery\MockInterface;

test('execute: 指定したスラグの記事が存在する場合、そのEntityを返すこと', function () {
    // 1. 準備
    $slug = 'existing-slug';
    $expectedEntity = new ArticleEntity(
        id: 1,
        userId: 1,
        title: 'テスト記事',
        slug: $slug,
        content: '本文',
        status: ArticleStatus::Published
    );

    /** @var ArticleRepositoryInterface|MockInterface $repository */
    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    $repository->shouldReceive('findBySlug')
        ->once()
        ->with($slug)
        ->andReturn($expectedEntity);

    $useCase = new FindArticleBySlugUseCase($repository);

    // 2. 実行
    $result = $useCase->execute($slug);

    // 3. 検証
    expect($result)->toBe($expectedEntity);
});

test('execute: 指定したスラグの記事が存在しない場合、ModelNotFoundExceptionを投げること', function () {
    // 1. 準備
    $slug = 'non-existent-slug';

    /** @var ArticleRepositoryInterface|MockInterface $repository */
    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    $repository->shouldReceive('findBySlug')
        ->once()
        ->with($slug)
        ->andReturn(null); // 見つからないケース

    $useCase = new FindArticleBySlugUseCase($repository);

    // 2. 実行 & 3. 検証
    // 例外が投げられることを確認
    expect(fn() => $useCase->execute($slug))
        ->toThrow(ModelNotFoundException::class);
});