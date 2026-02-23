<?php

namespace Tests\Unit\UseCases\Article;

use App\UseCases\Article\FindArticleBySlugUseCase;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Interfaces\PermissionServiceInterface;
use App\Domain\Entities\Article as ArticleEntity;
use App\Domain\Enums\ArticleStatus;
use App\Enums\PermissionType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Mockery;

beforeEach(function() {
    $this->repository = Mockery::mock(ArticleRepositoryInterface::class);
    $this->permissionService = Mockery::mock(PermissionServiceInterface::class);
});

test('execute: 指定したスラグの記事が存在する場合、そのEntityを返すこと', function () {
    // 1. 準備
    $slug = 'existing-slug';
    $userId = 1;
    $expectedEntity = new ArticleEntity(
        id: $userId,
        userId: 1,
        title: 'テスト記事',
        slug: $slug,
        content: '本文',
        status: ArticleStatus::Published
    );

    $this->repository->shouldReceive('findBySlug')
        ->once()
        ->with($slug)
        ->andReturn($expectedEntity);

    // 公開済みの記事は認可チェックはされない
    $useCase = new FindArticleBySlugUseCase($this->repository, $this->permissionService);

    // 2. 実行
    $result = $useCase->execute($slug, $userId);

    // 3. 検証
    expect($result)->toBe($expectedEntity);
});

test('execute: 公開済みの記事は、認可チェックなしで取得できること', function () {
    $slug = 'public-slug';
    $userId = 1;
    $expectedEntity = new ArticleEntity(
        id: 100,
        userId: 2, // 他人の記事
        title: '公開記事',
        slug: $slug,
        content: '本文',
        status: ArticleStatus::Published // 公開済み
    );

    $this->repository->shouldReceive('findBySlug')->once()->with($slug)->andReturn($expectedEntity);

    // Published の場合は PermissionService は呼ばれないので、shouldReceive は書かない

    $useCase = new FindArticleBySlugUseCase($this->repository, $this->permissionService);
    $result = $useCase->execute($slug, $userId);

    expect($result)->toBe($expectedEntity);
});

test('execute: 下書き記事でも、本人であれば取得できること', function () {
    $slug = 'draft-slug';
    $userId = 1;
    $expectedEntity = new ArticleEntity(
        id: 100,
        userId: $userId, // 自分
        title: '自分の下書き',
        slug: $slug,
        content: '本文',
        status: ArticleStatus::Draft // 下書き
    );

    $this->repository->shouldReceive('findBySlug')->once()->with($slug)->andReturn($expectedEntity);

    // 下書きの場合は認可チェックが走る
    $this->permissionService->shouldReceive('canUserPerformAction')
        ->once()
        ->with($userId, PermissionType::EDIT_ANY_ARTICLE, $expectedEntity)
        ->andReturn(true);

    $useCase = new FindArticleBySlugUseCase($this->repository, $this->permissionService);
    $result = $useCase->execute($slug, $userId);

    expect($result)->toBe($expectedEntity);
});

test('execute: 他人の下書き記事を閲覧しようとすると AccessDeniedHttpException を投げること', function () {
    $slug = 'others-draft';
    $userId = 1; // 自分
    $othersEntity = new ArticleEntity(
        id: 100,
        userId: 999, // 他人
        title: '他人の下書き',
        slug: $slug,
        content: '本文',
        status: ArticleStatus::Draft
    );

    $this->repository->shouldReceive('findBySlug')->once()->with($slug)->andReturn($othersEntity);

    // 認可サービスが false を返す設定
    $this->permissionService->shouldReceive('canUserPerformAction')
        ->once()
        ->andReturn(false);

    $useCase = new FindArticleBySlugUseCase($this->repository, $this->permissionService);

    expect(fn() => $useCase->execute($slug, $userId))
        ->toThrow(AccessDeniedHttpException::class);
});

test('execute: 指定したスラグの記事が存在しない場合、ModelNotFoundExceptionを投げること', function () {
    $slug = 'non-existent-slug';
    $userId = 1;

    $this->repository->shouldReceive('findBySlug')->once()->with($slug)->andReturn(null);

    // ここでは認可チェックまで到達しないため、PermissionService の設定は不要

    $useCase = new FindArticleBySlugUseCase($this->repository, $this->permissionService);

    expect(fn() => $useCase->execute($slug, $userId))
        ->toThrow(ModelNotFoundException::class);
});