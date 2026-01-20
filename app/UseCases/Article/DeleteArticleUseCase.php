<?php

namespace App\UseCases\Article;

use App\Domain\Interfaces\ArticleRepositoryInterface;

class DeleteArticleUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}

    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}