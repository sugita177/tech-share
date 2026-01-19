<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Article;
use Illuminate\Support\Collection;

interface ArticleRepositoryInterface
{
    /**
     * IDで記事を取得
     */
    public function findById(int $id): ?Article;

    /**
     * 全記事を取得
     */
    public function findAll(): array; // ArticleEntity の配列を返す

    /**
     * 記事を保存（新規作成・更新両用）
     */
    public function save(Article $article): Article;

    /**
     * 記事を削除
     */
    public function delete(int $id): void;

    /**
     * スラグ（URL用文字列）で記事を取得
     */
    public function findBySlug(string $slug): ?Article;

    /**
     * 指定されたスラグが既に存在するか確認する
     */
    public function existsBySlug(string $slug): bool;
}