<?php

namespace Tests\Unit\UseCases\Article;

use App\Domain\Entities\Article as ArticleEntity;
use App\UseCases\Article\DeleteArticleUseCase;
use App\Domain\Enums\ArticleStatus;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Interfaces\PermissionServiceInterface;
use App\Domain\Interfaces\TransactionManagerInterface;
use App\Enums\PermissionType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Mockery;

beforeEach(function () {
    $this->repository = Mockery::mock(ArticleRepositoryInterface::class);
    $this->permissionService = Mockery::mock(PermissionServiceInterface::class);
    $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);

    // TransactionManagerのモック設定
    $this->transactionManager
        ->shouldReceive('run')
        ->andReturnUsing(function ($callback) {
            // runメソッドに渡されたクロージャ($callback)を
            // そのまま実行して、その結果を返す
            return $callback();
        });
    
    $this->useCase = new DeleteArticleUseCase($this->repository, $this->permissionService, $this->transactionManager);

    // 共通のダミー記事Entity
    $this->dummyArticle = new ArticleEntity(
        id: 123,
        userId: 10,
        title: 'Test',
        slug: 'test',
        content: '...',
        status: ArticleStatus::Published
    );
});

test('execute: 権限がある場合、正常にリポジトリの削除メソッドが呼ばれること', function () {
    // 1. 準備
    $this->repository->shouldReceive('findById')->once()->with(123)->andReturn($this->dummyArticle);
    
    // 認可パス
    $this->permissionService->shouldReceive('canUserPerformAction')
        ->once()
        ->with(10, PermissionType::DELETE_ANY_ARTICLE, $this->dummyArticle)
        ->andReturn(true);

    $this->repository->shouldReceive('delete')->once()->with(123);

    // 2. 実行
    $this->useCase->execute(123, 10);

    // 3. 検証 (Mockeryの期待値通りならパス)
    expect(true)->toBeTrue();
});

test('execute: 権限がない場合、AccessDeniedHttpExceptionを投げること', function () {
    // 1. 準備
    $this->repository->shouldReceive('findById')->once()->andReturn($this->dummyArticle);
    
    // 認可失敗をシミュレート
    $this->permissionService->shouldReceive('canUserPerformAction')
        ->once()
        ->andReturn(false);

    // 削除は呼ばれないはず
    $this->repository->shouldNotReceive('delete');

    // 2. 実行 & 3. 検証
    expect(fn() => $this->useCase->execute(123, 999))
        ->toThrow(AccessDeniedHttpException::class, 'この記事を削除する権限がありません。');
});

test('execute: 記事が存在しない場合、ModelNotFoundExceptionを投げること', function () {
    // 1. 準備
    $this->repository->shouldReceive('findById')->once()->with(999)->andReturn(null);

    // 2. 実行 & 3. 検証
    expect(fn() => $this->useCase->execute(999, 10))
        ->toThrow(ModelNotFoundException::class);
});