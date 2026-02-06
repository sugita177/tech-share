<?php

namespace Tests\Unit\UseCases\Article;

use Tests\TestCase;
use App\Domain\Entities\Article;
use App\Domain\Enums\ArticleStatus;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Interfaces\PermissionServiceInterface;
use App\Domain\Interfaces\TransactionManagerInterface;
use App\Enums\PermissionType;
use App\UseCases\Article\UpdateArticleInput;
use App\UseCases\Article\UpdateArticleUseCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

// 共通のセットアップ（Mockの初期化）
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
});

describe('純粋なロジックのテスト', function () {
    test('execute: 正しい入力データで記事を更新し、更新後のEntityを返すこと', function () {
        // 1. 準備
        $articleId = 1;
        $userId = 10;
        
        $currentEntity = new Article(id: $articleId, userId: $userId, title: '旧', slug: 'old', content: '..', status: ArticleStatus::Draft);
        $updatedEntity = new Article(id: $articleId, userId: $userId, title: '新', slug: 'new', content: '..', status: ArticleStatus::Published);
    
        // 認可チェック: OKを返すように設定
        $this->permissionService->shouldReceive('canUserPerformAction')
            ->once()
            ->with($userId, PermissionType::EDIT_ANY_ARTICLE, $currentEntity)
            ->andReturn(true);
    
        $this->repository->shouldReceive('findById')->once()->with($articleId)->andReturn($currentEntity);
        $this->repository->shouldReceive('existsBySlug')->once()->andReturn(false);
        $this->repository->shouldReceive('update')->once()->andReturn($updatedEntity);
    
        // インスタンス化時に両方のモックを渡す
        $useCase = new UpdateArticleUseCase($this->repository, $this->permissionService, $this->transactionManager);
        
        $input = new UpdateArticleInput(id: $articleId, userId: $userId, title: '新', content: '..', slug: 'new', status: ArticleStatus::Published);
    
        // 2. 実行
        $result = $useCase->execute($input);
    
        // 3. 検証
        expect($result)->toBe($updatedEntity);
    });
    
    /**
     * 認可ロジックに特化したテスト
     */
    describe('認可チェックのテスト', function () {
    
        test('記事の所有者であれば、更新が可能であること', function () {
            $userId = 10;
            $article = new Article(id: 1, userId: $userId, title: '..', slug: '..', content: '..', status: ArticleStatus::Draft);
    
            $this->repository->shouldReceive('findById')->andReturn($article);
            
            // 所有者のため PermissionService が true を返す想定
            $this->permissionService->shouldReceive('canUserPerformAction')
                ->once()
                ->with($userId, PermissionType::EDIT_ANY_ARTICLE, $article)
                ->andReturn(true);
    
            // その後のバリデーション等は正常系として設定
            $this->repository->shouldReceive('existsBySlug')->andReturn(false);
            $this->repository->shouldReceive('update')->andReturn($article);
    
            $useCase = new UpdateArticleUseCase($this->repository, $this->permissionService, $this->transactionManager);
            $input = new UpdateArticleInput(
                id: 1, 
                userId: 10, 
                title: '新', 
                content: '..', 
                slug: 'taken-slug',
                status: ArticleStatus::Published
            );
            expect($useCase->execute($input))->toBeInstanceOf(Article::class);
        });
    
        test('所有者でなくても、管理権限があれば更新が可能であること', function () {
            $adminId = 999;
            $ownerId = 10;
            $article = new Article(id: 1, userId: $ownerId, title: '..', slug: '..', content: '..', status: ArticleStatus::Draft);
    
            $this->repository->shouldReceive('findById')->andReturn($article);
            
            // 管理者のため、PermissionService が内部で Role を判断して true を返す想定
            $this->permissionService->shouldReceive('canUserPerformAction')
                ->once()
                ->with($adminId, PermissionType::EDIT_ANY_ARTICLE, $article)
                ->andReturn(true);
    
            $this->repository->shouldReceive('existsBySlug')->andReturn(false);
            $this->repository->shouldReceive('update')->andReturn($article);
    
            $useCase = new UpdateArticleUseCase($this->repository, $this->permissionService, $this->transactionManager);
            $input = new UpdateArticleInput(
                id: 1,
                userId: $adminId,
                title: '新',
                content: '..',
                slug: 'slug-name',
                status: ArticleStatus::Published
            );
    
            expect($useCase->execute($input))->toBeInstanceOf(Article::class);
        });
    
        test('所有者ではなく、権限もない場合は AccessDeniedHttpException を投げること', function () {
            $strangerId = 555;
            $ownerId = 10;
            $article = new Article(id: 1, userId: $ownerId, title: '..', slug: '..', content: '..', status: ArticleStatus::Draft);
    
            $this->repository->shouldReceive('findById')->andReturn($article);
            
            // 認可失敗（false）を返す
            $this->permissionService->shouldReceive('canUserPerformAction')
                ->once()
                ->with($strangerId, PermissionType::EDIT_ANY_ARTICLE, $article)
                ->andReturn(false);
    
            $useCase = new UpdateArticleUseCase($this->repository, $this->permissionService, $this->transactionManager);
            $input = new UpdateArticleInput(
                id: 1,
                userId: $strangerId,
                title: '新',
                content: '..',
                slug: 'slug-name',
                status: ArticleStatus::Published
            );
    
            expect(fn() => $useCase->execute($input))
                ->toThrow(AccessDeniedHttpException::class, '権限がありません。');
        });
    });
    
    test('execute: 更新対象の記事が存在しない場合、ModelNotFoundExceptionを投げること', function () {
        $this->repository->shouldReceive('findById')->andReturn(null);
    
        $useCase = new UpdateArticleUseCase($this->repository, $this->permissionService, $this->transactionManager);
        $input = new UpdateArticleInput(
                id: 999,
                userId: 1,
                title: '新',
                content: '..',
                slug: 'slug-name',
                status: ArticleStatus::Published
            );
    
        expect(fn() => $useCase->execute($input))->toThrow(ModelNotFoundException::class);
    });
});

describe('Laravelコンテナが必要なテスト', function () {
    // このブロック内のみ TestCase (Laravel起動) を適用する
    uses(TestCase::class);
    /**
     * 既存のバリデーション・異常系テストの修正
     * (PermissionService が常に true を返すように設定)
     */
    test('execute: 重複したスラグを指定した場合、ValidationExceptionを投げること', function () {
        $currentArticle = new Article(id: 1, userId: 1, title: '旧', slug: 'old-slug', content: '..', status: ArticleStatus::Draft);

        $this->repository->shouldReceive('findById')->andReturn($currentArticle);
        $this->repository->shouldReceive('existsBySlug')->andReturn(true);

        // 認可は通す
        $this->permissionService->shouldReceive('canUserPerformAction')->andReturn(true);

        $useCase = new UpdateArticleUseCase($this->repository, $this->permissionService, $this->transactionManager);
        $input = new UpdateArticleInput(
                id: 1,
                userId: 1,
                title: '新',
                content: '..',
                slug: 'taken-slug',
                status: ArticleStatus::Published
            );

        expect(fn() => $useCase->execute($input))->toThrow(ValidationException::class);
    });
});
