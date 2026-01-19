<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Article as ArticleEntity;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Models\Article as EloquentArticle;
use Illuminate\Support\Collection;

class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function findById(int $id): ?ArticleEntity
    {
        $article = EloquentArticle::find($id);
        return $article ? $this->toEntity($article) : null;
    }

    public function save(ArticleEntity $entity): ArticleEntity
    {
        // EntityのデータをEloquentモデルに詰め替える
        $model = EloquentArticle::updateOrCreate(
            ['id' => $entity->id],
            [
                'user_id' => $entity->userId,
                'title' => $entity->title,
                'slug' => $entity->slug,
                'content' => $entity->content,
                'status' => $entity->status,
                'view_count' => $entity->viewCount,
            ]
        );

        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        EloquentArticle::destroy($id);
    }

    public function findBySlug(string $slug): ?ArticleEntity
    {
        $article = EloquentArticle::where('slug', $slug)->first();
        return $article ? $this->toEntity($article) : null;
    }

    /**
     * 全記事を取得
     */
    public function findAll(): array
    {
        return \App\Models\Article::latest() // 新しい順
            ->get()
            ->map(fn($model) => new ArticleEntity(
                id: $model->id,
                userId: $model->user_id,
                title: $model->title,
                slug: $model->slug,
                content: $model->content,
                status: $model->status,
                viewCount: $model->view_count
            ))
            ->all();
    }

    /**
     * Eloquentモデルを純粋なDomain Entityに変換する（変換メソッド）
     */
    private function toEntity(EloquentArticle $model): ArticleEntity
    {
        return new ArticleEntity(
            id: $model->id,
            userId: $model->user_id,
            title: $model->title,
            slug: $model->slug,
            content: $model->content,
            status: $model->status,
            viewCount: $model->view_count ?? 0,// null なら 0 を渡す
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    /**
     * 指定されたスラグが既に存在するか確認する
     */
    public function existsBySlug(string $slug): bool
    {
        // Eloquentの exists() メソッドを使うのが最も効率的です
        return EloquentArticle::where('slug', $slug)->exists();
    }
}