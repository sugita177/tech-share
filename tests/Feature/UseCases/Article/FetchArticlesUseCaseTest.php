<?php

use App\UseCases\Article\FetchArticlesUseCase;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Entities\Article;
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
        ->with($perPage)
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
        ->with(100)
        ->andReturn($mockPaginator);

    $useCase = new FetchArticlesUseCase($repository);
    $result = $useCase->execute(100);

    // 検証（アサーション）
    // ユースケースの戻り値が、リポジトリから返されたものと同一であること
    expect($result)->toBe($mockPaginator);
    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});