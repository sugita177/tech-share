<?php

namespace App\UseCases\Article;

use App\Domain\Entities\Article;
use App\Domain\Enums\ArticleStatus;
use App\Enums\PermissionType;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Interfaces\PermissionServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FindArticleBySlugUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $repository,
        private PermissionServiceInterface $permissionService
    ) {}

    public function execute(string $slug, int $currentUserId): Article
    {
        $article = $this->repository->findBySlug($slug);

        if (!$article) {
            // Laravel標準のこの例外を投げると、自動的に404レスポンスになります
            throw new ModelNotFoundException();
        }

        // 1. 公開済みなら誰でも（ログインユーザーなら誰でも）OK
        if ($article->status === ArticleStatus::Published) {
            return $article;
        }

        // 2. 下書きの場合の認可チェック
        // ここに来る時点で $currentUserId は必ず存在する（型で保証）
        $canView = $this->permissionService->canUserPerformAction(
            $currentUserId,
            PermissionType::EDIT_ANY_ARTICLE,
            $article
        );

        if (!$canView) {
            throw new AccessDeniedHttpException('この記事を閲覧する権限がありません。');
        }

        return $article;
    }
}