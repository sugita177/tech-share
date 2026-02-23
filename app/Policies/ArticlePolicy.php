<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use App\Enums\PermissionType;
use App\Domain\Enums\ArticleStatus;

class ArticlePolicy
{
    /**
     * 閲覧権限の判定
     * 特定の1件の記事を表示できるかどうか
     */
    public function view(User $user, Article $article): bool
    {
        // 1. 公開済みの記事は、社内の全ユーザーが観測可能
        if ($article->status === ArticleStatus::Published->value) {
            return true;
        }

        // 2. 下書き（Draft）の場合は、所有者本人のみ許可
        if ($user->id === $article->user_id) {
            return true;
        }

        // 3. または、管理権限（他人の下書きを見れる権限）があれば許可
        return $user->hasPermission(PermissionType::EDIT_ANY_ARTICLE);
    }
    
    /**
     * 更新権限の判定
     */
    public function update(User $user, Article $article): bool
    {
        // 1. 記事の所有者であれば許可
        if ($user->id === $article->user_id) {
            return true;
        }

        // 2. または、Spatieの「edit any article」権限を持っていれば許可
        return $user->hasPermission(PermissionType::EDIT_ANY_ARTICLE);
    }

    /**
     * 削除権限の判定
     */
    public function delete(User $user, Article $article): bool
    {
        // 1. 記事の所有者であれば許可
        if ($user->id === $article->user_id) {
            return true;
        }

        // 2. または、Spatieの「delete any article」権限を持っていれば許可
        return $user->hasPermission(PermissionType::DELETE_ANY_ARTICLE);
    }
}