<?php

use App\UseCases\Article\CreateArticleUseCase;
use App\UseCases\Article\CreateArticleInput;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Entities\Article;
use Mockery\MockInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


/**
 * 正常系テスト
 */
test('ユーザーがスラグを指定した場合、そのスラグが使用されること', function () {
    $input = new CreateArticleInput(
        userId: 1,
        title: 'テストタイトル',
        content: '本文',
        slug: 'user-custom-slug',
        status: 'published'
    );

    $repository = Mockery::mock(ArticleRepositoryInterface::class);
    
    // 指定されたスラグの重複チェックが行われ、false（重複なし）を返す
    $repository->shouldReceive('existsBySlug')
        ->once()
        ->with('user-custom-slug')
        ->andReturn(false);

    $repository->shouldReceive('save')
        ->once()
        ->andReturnUsing(fn (Article $article) => $article);

    $useCase = new CreateArticleUseCase($repository);
    $result = $useCase->execute($input);

    expect($result->slug)->toBe('user-custom-slug');
});

test('スラグを指定しない場合、重複しないまでランダム生成が試行されること', function () {
    $input = new CreateArticleInput(
        userId: 1,
        title: 'テストタイトル',
        content: '本文',
        slug: null,
        status: 'published'
    );

    $repository = Mockery::mock(ArticleRepositoryInterface::class);

    // 1回目：重複(true), 2回目：重複なし(false)
    $repository->shouldReceive('existsBySlug')
        ->times(2)
        ->andReturnValues([true, false]);

    $repository->shouldReceive('save')->once()->andReturnUsing(fn ($a) => $a);

    $useCase = new CreateArticleUseCase($repository);
    $result = $useCase->execute($input);

    expect($result->slug)->toHaveLength(14);
});

/**
 * 異常系テスト
 */
test('ユーザー指定のスラグが既に存在する場合、InvalidArgumentExceptionを投げること', function () {
    $input = new CreateArticleInput(
        userId: 1,
        title: 'テストタイトル',
        content: '本文',
        slug: 'already-taken-slug',
        status: 'published'
    );

    $repository = Mockery::mock(ArticleRepositoryInterface::class);

    // 重複チェックで true を返す
    $repository->shouldReceive('existsBySlug')
        ->once()
        ->with('already-taken-slug')
        ->andReturn(true);

    // 保存処理は呼ばれない
    $repository->shouldNotReceive('save');

    $useCase = new CreateArticleUseCase($repository);

    // 例外の検証
    expect(fn() => $useCase->execute($input))
        ->toThrow(function (ValidationException $e) {
            // エラーメッセージの中身を検証
            expect($e->errors())->toHaveKey('slug');
            expect($e->errors()['slug'][0])->toBe('指定されたスラグは既に使用されています。');
        });
});

test('スラグの自動生成が10回連続で重複した際、RuntimeExceptionを投げること', function () {
    $input = new CreateArticleInput(
        userId: 1,
        title: 'テストタイトル',
        content: '本文',
        slug: null,
        status: 'published'
    );

    $repository = Mockery::mock(ArticleRepositoryInterface::class);

    // 常に重複していると回答
    $repository->shouldReceive('existsBySlug')
        ->times(10) // 最大試行回数
        ->andReturn(true);

    $useCase = new CreateArticleUseCase($repository);

    expect(fn() => $useCase->execute($input))
        ->toThrow(RuntimeException::class, 'スラグの自動生成に失敗しました。');
});