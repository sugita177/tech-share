<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Article;
use App\Enums\PermissionType;

interface PermissionServiceInterface
{
    /**
     * ユーザーが特定の記事に対して編集・削除等の権限を持っているか判定
     */
    public function canUserPerformAction(int $userId, PermissionType $permission, Article $article): bool;
}