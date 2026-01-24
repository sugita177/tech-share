<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Article as ArticleEntity;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Models\Article as EloquentArticle;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


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
        // 見つからない場合は ModelNotFoundException (404) を投げる
        $model = EloquentArticle::findOrFail($id);
        $model->delete();
    }

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        $paginator = \App\Models\Article::latest()->paginate($perPage);

        // EloquentモデルのコレクションをEntityのコレクションに変換
        // ページネーション情報を維持したまま中身だけ入れ替えます
        $paginator->getCollection()->transform(fn($model) => new ArticleEntity(
            id: $model->id,
            userId: $model->user_id,
            title: $model->title,
            slug: $model->slug,
            content: $model->content,
            status: $model->status,
            viewCount: $model->view_count,
            createdAt: $model->created_at
        ));

        return $paginator;
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

    public function findBySlug(string $slug): ?ArticleEntity
    {
        $model = EloquentArticle::where('slug', $slug)->first();
    
        if (!$model) {
            return null;
        }
    
        return new ArticleEntity(
            id: $model->id,
            userId: $model->user_id,
            title: $model->title,
            slug: $model->slug,
            content: $model->content,
            status: $model->status,
            viewCount: $model->view_count
        );
    }

    /**
     * 記事を更新する
     */
    public function update(ArticleEntity $article): ArticleEntity
    {
        // findOrFail を使うことで、万が一存在しないIDが渡されたらここで404を投げます
        $model = ELoquentArticle::findOrFail($article->id);

        // user_id は更新対象に含めない
        $model->fill([
            'title'   => $article->title,
            'slug'    => $article->slug,
            'content' => $article->content,
            'status'  => $article->status,
        ])->save();

        // 呼び出し元にはDBから最新の状態（正しいuser_id含む）を再構成して返すのが安全
        return $this->findById($model->id); 
    }
}