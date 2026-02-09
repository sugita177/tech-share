<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Article;
use App\Domain\Enums\ArticleStatus;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


interface ArticleRepositoryInterface
{
    /**
     * IDで記事を取得
     */
    public function findById(int $id): ?Article;

    /**
     * ページングして複数記事を取得
     */
    public function paginate(int $perPage = 10, ?ArticleStatus $status = null): LengthAwarePaginator;

    /**
     * 記事を保存（新規作成・更新両用）
     */
    public function save(Article $article): Article;

    /**
     * 記事を削除
     */
    public function delete(int $id): void;

    /**
     * 指定されたスラグが既に存在するか確認する
     */
    public function existsBySlug(string $slug): bool;

    /**
     * スラグ（URL用文字列）で記事を取得
     * @param string $slug
     * @return Article|null 見つからない場合はnullを返す
     */
    public function findBySlug(string $slug): ?Article;

    /**
     * 記事を更新する
     */
    public function update(Article $article): Article;
}