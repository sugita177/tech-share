<?php

namespace Tests\Unit\UseCases\Article;

use App\UseCases\Article\DeleteArticleUseCase;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Mockery\MockInterface;

test('execute: 指定したIDがリポジトリの削除メソッドに渡されること', function () {
    // 1. 準備
    $articleId = 123;
    
    /** @var ArticleRepositoryInterface|MockInterface $repository */
    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    
    // delete メソッドが指定した ID で 1 回呼ばれることを期待
    $repository->shouldReceive('delete')
        ->once()
        ->with($articleId)
        ->andReturnNull();

    $useCase = new DeleteArticleUseCase($repository);

    // 2. 実行
    $useCase->execute($articleId);

    // 3. 検証 (Mockery の once() によって検証される)
    expect(true)->toBeTrue(); 
});

test('execute: リポジトリで例外が発生した場合、そのまま例外が伝播すること', function () {
    // 1. 準備
    $articleId = 999;
    
    /** @var ArticleRepositoryInterface|MockInterface $repository */
    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    
    // リポジトリが例外を投げるように設定
    $repository->shouldReceive('delete')
        ->with($articleId)
        ->andThrow(new ModelNotFoundException());

    $useCase = new DeleteArticleUseCase($repository);

    // 2. 実行 & 3. 検証
    expect(fn() => $useCase->execute($articleId))
        ->toThrow(ModelNotFoundException::class);
});