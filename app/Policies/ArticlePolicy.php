<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use App\Enums\PermissionType;

class ArticlePolicy
{
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