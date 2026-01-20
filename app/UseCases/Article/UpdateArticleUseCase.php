<?php

namespace App\UseCases\Article;

use App\Domain\Entities\Article;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class UpdateArticleUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}

    public function execute(UpdateArticleInput $input): Article
    {
        // 1. まず現在のデータを取得（存在チェックも兼ねる）
        $currentArticle = $this->repository->findById($input->id);
        if (!$currentArticle) {
            throw new ModelNotFoundException();
        }

        // 2. スラグの重複チェック（変更がある場合のみ）
        if ($input->slug && $input->slug !== $currentArticle->slug) {
            if ($this->repository->existsBySlug($input->slug)) {
                // ValidationException::withMessages を使う
                throw ValidationException::withMessages([
                    'slug' => ['指定されたスラグは既に使用されています。']
                ]);
            }
        }

        // 3. 既存のEntityをベースに、変更したい値だけを上書きする
        $updatedArticle = new Article(
            id: $currentArticle->id,
            userId: $currentArticle->userId, // 元の値を保持
            title: $input->title,
            slug: $input->slug ?? $currentArticle->slug, // 指定がなければ元のスラグ
            content: $input->content,
            status: $input->status,
            viewCount: $currentArticle->viewCount // 元の値を保持
        );

        return $this->repository->update($updatedArticle);
    }
}