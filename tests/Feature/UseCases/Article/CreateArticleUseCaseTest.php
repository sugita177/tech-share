<?php

use App\UseCases\Article\CreateArticleUseCase;
use App\UseCases\Article\CreateArticleInput;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Entities\Article;
use Mockery\MockInterface;

test('記事作成ユースケースが正しく実行され、リポジトリが呼ばれること', function () {
    // 1. 準備 (Inputデータの作成)
    $input = new CreateArticleInput(
        userId: 1,
        title: 'テスト記事タイトル',
        slug: 'abc',
        content: 'テスト本文です。',
        status: 'published'
    );

    // 2. Mockの設定 (ArticleRepositoryInterfaceをモック化)
    /** @var ArticleRepositoryInterface|MockInterface $repository */
    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    
    // saveメソッドが1回呼ばれ、特定のEntityを返すことを期待する
    $repository->shouldReceive('save')
        ->once()
        ->with(Mockery::type(Article::class))
        ->andReturnUsing(fn (Article $article) => $article);

    // 3. 実行
    $useCase = new CreateArticleUseCase($repository);
    $result = $useCase->execute($input);

    // 4. 検証 (アサーション)
    expect($result)->toBeInstanceOf(Article::class);
    expect($result->title)->toBe('テスト記事タイトル');
    expect($result->slug)->toBe('abc');
    expect($result->status)->toBe('published');
});

test('スラグを指定しない場合、ランダムな文字列が生成されること', function () {
    $input = new CreateArticleInput(
        userId: 1,
        title: 'テストタイトル',
        content: '本文',
        slug: null // スラグを指定しない
    );

    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    // existsBySlug が呼ばれたら、最初は「存在しない(false)」を返すように設定
    $repository->shouldReceive('existsBySlug')
        ->once() // 1回呼ばれることを期待
        ->andReturn(false); // 重複していないと回答
    $repository->shouldReceive('save')->once()->andReturnUsing(fn ($a) => $a);

    $useCase = new CreateArticleUseCase($repository);
    $result = $useCase->execute($input);

    expect($result->slug)->not->toBeEmpty();
    expect(strlen($result->slug))->toBe(14); // 14桁であることを確認
});

test('スラグが重複した場合、再生成されること', function () {
    $input = new CreateArticleInput(
        userId: 1,
        title: 'テストタイトル',
        content: '本文',
        slug: null // スラグを指定しない
    );

    $repository = Mockery::mock(ArticleRepositoryInterface::class);

    // 1回目はtrue（重複あり）、2回目はfalse（重複なし）を順番に返す
    $repository->shouldReceive('existsBySlug')
        ->times(2) // 合計2回呼ばれることを期待
        ->andReturnValues([true, false]); 

    $repository->shouldReceive('save')
        ->once()
        ->andReturnUsing(fn ($article) => $article);

    $useCase = new CreateArticleUseCase($repository);
    $result = $useCase->execute($input);

    // 検証：スラグが空でなく、2回生成が試みられていること
    expect($result->slug)->not->toBeEmpty();
    expect(strlen($result->slug))->toBe(14); // 14桁であることを確認
});