<?php

namespace App\UseCases\Article;

use App\Domain\Enums\ArticleStatus;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FetchMyArticlesUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}

    /**
     * @param int $userId ログインユーザーのID
     * @param int $perPage
     * @param ArticleStatus|null $status タブ切り替えなどで特定の状態のみ見たい場合用
     */
    public function execute(int $userId, int $perPage = 10, ?ArticleStatus $status = null): LengthAwarePaginator
    {
        // 自分の ID を指定し、ステータスは任意（null なら全取得）でリポジトリを叩く
        return $this->repository->paginate($perPage, $status, $userId);
    }
}