<?php

namespace App\UseCases\Article;

use App\Domain\Entities\Article;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Interfaces\TransactionManagerInterface;
use App\UseCases\Article\CreateArticleInput;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateArticleUseCase
{
    /**
     * スラグ自動生成の最大試行数
     */
    private const MAX_ATTEMPTS = 10;

    /**
     * DIコンテナによってインターフェースの実装が注入される
     */
    public function __construct(
        private ArticleRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager
    ) {}

    /**
     * 記事作成の実行
     * * @param array $data バリデーション済みの入力データ
     * @return Article 作成されたエンティティ
     */
    public function execute(CreateArticleInput $input): Article
    {
        // トランザクションの「場」の中で処理を実行する
        return $this->transactionManager->run(function () use ($input) {
            // 1. スラグの決定
            if ($input->slug) {
                // ユーザー指定がある場合：重複していたらエラーを投げる
                if ($this->repository->existsBySlug($input->slug)) {
                    throw ValidationException::withMessages([
                    'slug' => ['指定されたスラグは既に使用されています。']
                ]);
                }
                $slug = $input->slug;
            } else {
                // ユーザー指定がない場合：重複しないまでループ（回数制限付きがより安全）
                $attempts = 0;
                do {
                    $slug = Str::random(14);
                    $attempts++;
                    if ($attempts > self::MAX_ATTEMPTS) {
                        throw new \RuntimeException('スラグの自動生成に失敗しました。');
                    }
                } while ($this->repository->existsBySlug($slug));
            }

            // 2. エンティティの構築
            $article = new Article(
                id: null,
                userId: $input->userId,
                title: $input->title,
                slug: $slug,
                content: $input->content,
                status: $input->status,
                viewCount: 0
            );

            // Repositoryを介して保存
            // ここで将来的に「投稿後にSlack通知を送るJobをディスパッチする」などの処理も追加できます
            return $this->repository->save($article);
        });
    }
}