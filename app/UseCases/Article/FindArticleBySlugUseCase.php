<?php

namespace App\UseCases\Article;

use App\Domain\Entities\Article;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FindArticleBySlugUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}

    public function execute(string $slug): Article
    {
        $article = $this->repository->findBySlug($slug);

        if (!$article) {
            // Laravel標準のこの例外を投げると、自動的に404レスポンスになります
            throw new ModelNotFoundException("Article not found with slug: {$slug}");
        }

        return $article;
    }
}