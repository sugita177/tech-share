<?php

namespace App\UseCases\Article;

use App\Domain\Interfaces\ArticleRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteArticleUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}

    public function execute(int $id, int $currentUserId): void
    {
        $article = $this->repository->findById($id);
        if (!$article) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        if ($article->userId !== $currentUserId) {
            throw new AccessDeniedHttpException('この記事をする削除する権限がありません。');
        }

        $this->repository->delete($id);
    }
}