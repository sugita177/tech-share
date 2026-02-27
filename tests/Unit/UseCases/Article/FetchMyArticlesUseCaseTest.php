<?php

use App\UseCases\Article\FetchMyArticlesUseCase;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Enums\ArticleStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;

test('execute: ログインユーザーのIDとデフォルト引数でリポジトリのpaginateが呼ばれること', function () {
    // 1. 準備
    $userId = 1;
    // リポジトリが返すべきダミーのパジネーターをモック
    $mockPaginator = Mockery::mock(LengthAwarePaginator::class);
    $repository = Mockery::mock(ArticleRepositoryInterface::class);

    // 期待値: paginate(デフォルトの10, 指定なしのnull, 渡したユーザーID) が呼ばれること
    $repository->shouldReceive('paginate')
        ->once()
        ->with(10, null, $userId)
        ->andReturn($mockPaginator);

    $useCase = new FetchMyArticlesUseCase($repository);

    // 2. 実行
    $result = $useCase->execute($userId);

    // 3. 検証
    expect($result)->toBe($mockPaginator);
});

test('execute: 指定した件数とステータスがリポジトリに正しく渡されること', function () {
    // 1. 準備
    $userId = 999;
    $perPage = 50;
    $status = ArticleStatus::Draft;
    
    $mockPaginator = Mockery::mock(LengthAwarePaginator::class);
    $repository = Mockery::mock(ArticleRepositoryInterface::class);

    // 期待値: 指定した引数 ($perPage, $status, $userId) がそのままリポジトリに渡されること
    $repository->shouldReceive('paginate')
        ->once()
        ->with($perPage, $status, $userId)
        ->andReturn($mockPaginator);

    $useCase = new FetchMyArticlesUseCase($repository);

    // 2. 実行
    $result = $useCase->execute($userId, $perPage, $status);

    // 3. 検証
    expect($result)->toBe($mockPaginator);
});