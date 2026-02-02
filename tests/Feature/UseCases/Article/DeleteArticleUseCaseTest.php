<?php

namespace Tests\Unit\UseCases\Article;

use App\Domain\Entities\Article as ArticleEntity;
use App\UseCases\Article\DeleteArticleUseCase;
use App\Domain\Enums\ArticleStatus;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Interfaces\PermissionServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Mockery\MockInterface;

test('execute: 指定したIDがリポジトリの削除メソッドに渡されること', function () {
    // 1. 準備
    $articleId = 123;
    $currentUserId = 1;
    
    /** @var ArticleRepositoryInterface|MockInterface $repository */
    $repository = Mockery::mock(ArticleRepositoryInterface::class);

    /** @var PermissionServiceInterface|MockInterface $permissionService */
    $permissionService = Mockery::mock(PermissionServiceInterface::class);

    // 1. 認可チェック用の findById を定義
    $repository->shouldReceive('findById')
        ->once()
        ->with($articleId)
        ->andReturn(new ArticleEntity(
            id: $articleId,
            userId: $currentUserId, // 実行者と同じIDにする（認可パス）
            title: 'Test',
            slug: 'test',
            content: '...',
            status: ArticleStatus::Published
        ));

    $permissionService->shouldReceive('canUserPerformAction')
        ->andReturn(true);
    
    // delete メソッドが指定した ID で 1 回呼ばれることを期待
    $repository->shouldReceive('delete')
        ->once()
        ->with($articleId)
        ->andReturnNull();

    $useCase = new DeleteArticleUseCase($repository, $permissionService);

    // 2. 実行
    $useCase->execute($articleId, $currentUserId);

    // 3. 検証 (Mockery の once() によって検証される)
    expect(true)->toBeTrue(); 
});

test('execute: リポジトリで例外が発生した場合、そのまま例外が伝播すること', function () {
    // 1. 準備
    $articleId = 999;
    $userId = 1;
    
    /** @var ArticleRepositoryInterface|MockInterface $repository */
    $repository = Mockery::mock(ArticleRepositoryInterface::class);

    /** @var PermissionServiceInterface|MockInterface $permissionService */
    $permissionService = Mockery::mock(PermissionServiceInterface::class);

    // 認可チェックをパスさせるために findById は成功させる
    $repository->shouldReceive('findById')
        ->once()
        ->with($articleId)
        ->andReturn(new ArticleEntity(
            id: $articleId,
            userId: $userId,
            title: 'Test',
            slug: 'test',
            content: '...',
            status: ArticleStatus::Published
        ));

    $permissionService->shouldReceive('canUserPerformAction')
        ->andReturn(true);
    
    // リポジトリが例外を投げるように設定
    $repository->shouldReceive('delete')
        ->with($articleId)
        ->andThrow(new ModelNotFoundException());

    $useCase = new DeleteArticleUseCase($repository, $permissionService);

    // 2. 実行 & 3. 検証
    expect(fn() => $useCase->execute($articleId, $userId))
        ->toThrow(ModelNotFoundException::class);
});