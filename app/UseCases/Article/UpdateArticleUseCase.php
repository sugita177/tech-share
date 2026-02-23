<?php

namespace App\UseCases\Article;

use App\Domain\Entities\Article;
use App\Domain\Interfaces\ArticleRepositoryInterface;
use App\Domain\Interfaces\PermissionServiceInterface;
use App\Domain\Interfaces\TransactionManagerInterface;
use App\Enums\PermissionType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UpdateArticleUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $repository,
        private PermissionServiceInterface $permissionService,
        private TransactionManagerInterface $transactionManager
    ) {}

    public function execute(UpdateArticleInput $input): Article
    {
        // トランザクションの「場」の中で処理を実行する
        /** @var Article */
        $result = $this->transactionManager->run(function () use ($input) {
            // 1. まず現在のデータを取得（存在チェックも兼ねる）
            $currentArticle = $this->repository->findById($input->id);
            if (!$currentArticle) {
                throw new ModelNotFoundException();
            }

            // 認可チェック
            $canUserUpdateArticle = $this->permissionService->canUserPerformAction(
                $input->userId, 
                PermissionType::EDIT_ANY_ARTICLE, 
                $currentArticle
            );
            if (!$canUserUpdateArticle) {
                throw new AccessDeniedHttpException('この記事を編集する権限がありません。');
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
        });
        
        return $result;
    }
}