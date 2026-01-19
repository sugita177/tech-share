<?php

namespace App\UseCases\Article;

use App\Domain\Interfaces\ArticleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FetchArticlesUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}

    public function execute(int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}