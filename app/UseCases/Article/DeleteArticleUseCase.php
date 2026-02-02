<?php

namespace App\UseCases\Article;

use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Interfaces\PermissionServiceInterface;
use App\Enums\PermissionType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteArticleUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $repository,
        private PermissionServiceInterface $permissionService
    ) {}

    public function execute(int $id, int $currentUserId): void
    {
        $article = $this->repository->findById($id);
        if (!$article) {
            throw new ModelNotFoundException();
        }

        // 認可チェック（Policyのdeleteメソッドを呼び出す）
        $canUserDeleteArticle = $this->permissionService->canUserPerformAction(
            $currentUserId, 
            PermissionType::DELETE_ANY_ARTICLE, 
            $article
        );
        if (!$canUserDeleteArticle) {
            throw new AccessDeniedHttpException('この記事を削除する権限がありません。');
        }

        $this->repository->delete($id);
    }
}