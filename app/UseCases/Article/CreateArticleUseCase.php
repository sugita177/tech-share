<?php

namespace App\UseCases\Article;

use App\Domain\Entities\Article;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\UseCases\Article\CreateArticleInput;
use Illuminate\Support\Str;

class CreateArticleUseCase
{
    /**
     * DIコンテナによってインターフェースの実装が注入される
     */
    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}

    /**
     * 記事作成の実行
     * * @param array $data バリデーション済みの入力データ
     * @return Article 作成されたエンティティ
     */
    public function execute(CreateArticleInput $input): Article
    {
        // 1. スラグの自動生成（ビジネスルールの一例）
        // 入力がなければランダム、あれば入力値を使用
        $slug = $input->slug ?: Str::random(14);
    
        // 自動生成時（入力がない時）に被った場合のみ再試行する
        if (!$input->slug) {
            while ($this->repository->existsBySlug($slug)) {
                $slug = Str::random(14);
            }
        }

        // 2. ドメインエンティティの構築
        $article = new Article(
            id: null,
            userId: $input->userId,
            title: $input->title,
            slug: $slug,
            content: $input->content,
            status: $input->status,
            viewCount: 0
        );

        // 3. Repositoryを介して保存
        // ここで将来的に「投稿後にSlack通知を送るJobをディスパッチする」などの処理も追加できます
        return $this->repository->save($article);
    }
}