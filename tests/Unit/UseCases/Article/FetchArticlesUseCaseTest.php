<?php

use App\UseCases\Article\FetchArticlesUseCase;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Entities\Article;
use App\Domain\Enums\ArticleStatus;
use Mockery\MockInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


test('execute: リポジトリのpaginateメソッドが正しい引数で呼び出されること', function () {
    $perPage = 5;
    $mockPaginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

    /** @var ArticleRepositoryInterface|MockInterface $repository */
    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    
    // paginate(5) が呼ばれることを期待
    $repository->shouldReceive('paginate')
        ->once()
        ->with($perPage, ArticleStatus::Published)
        ->andReturn($mockPaginator);

    $useCase = new FetchArticlesUseCase($repository);
    $result = $useCase->execute($perPage);

    expect($result)->toBe($mockPaginator);
});

test('execute: 指定した1ページあたりの件数がリポジトリに正しく渡されること', function () {
    // リポジトリが返すべきダミーのパジネーターを作成
    $mockPaginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

    /** @var ArticleRepositoryInterface|MockInterface $repository */
    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    
    // 境界値として 1 や 100 をテストしてみる
    $repository->shouldReceive('paginate')
        ->once()
        ->with(100, ArticleStatus::Published)
        ->andReturn($mockPaginator);

    $useCase = new FetchArticlesUseCase($repository);
    $result = $useCase->execute(100);

    // 検証（アサーション）
    // ユースケースの戻り値が、リポジトリから返されたものと同一であること
    expect($result)->toBe($mockPaginator);
    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});

test('execute: 引数を省略した場合、デフォルト値（10件）と公開ステータスがリポジトリに渡されること', function () {
    $mockPaginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    
    // 引数なしで呼ばれたら、裏では「10」と「Published」でリポジトリを叩いているはず
    $repository->shouldReceive('paginate')
        ->once()
        ->with(10, ArticleStatus::Published) // デフォルト値 10 を検証
        ->andReturn($mockPaginator);

    $useCase = new FetchArticlesUseCase($repository);
    
    // 引数なしで実行
    $result = $useCase->execute();

    expect($result)->toBe($mockPaginator);
});