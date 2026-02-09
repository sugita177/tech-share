<?php

namespace App\UseCases\Article;

use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Enums\ArticleStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FetchArticlesUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}

    public function execute(int $perPage = 10): LengthAwarePaginator
    {
        //「公開済み (Published)」を指定する
        return $this->repository->paginate($perPage, ArticleStatus::Published);
    }
}