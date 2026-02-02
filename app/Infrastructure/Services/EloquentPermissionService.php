<?php

namespace App\Infrastructure\Services;

use App\Domain\Interfaces\PermissionServiceInterface;
use App\Domain\Entities\Article as ArticleEntity;
use App\Enums\PermissionType;
use App\Models\User;
use App\Models\Article as ArticleModel;
use Illuminate\Support\Facades\Gate;

class EloquentPermissionService implements PermissionServiceInterface
{
    public function canUserPerformAction(int $userId, PermissionType $permission, ArticleEntity $articleEntity): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        // Policy等で「本人確認」と「Spatieの権限チェック」を統合して判定する場合
        // LaravelのGateを利用して、Eloquentモデルを引数に渡す
        $articleModel = ArticleModel::find($articleEntity->id);
        
        if (!$articleModel) return false;

        // Gate経由でPolicyを呼び出す（Policy側でSpatieのPermissionType::value等を使う）
        // 第1引数はPolicyのメソッド名。ここではEnumに応じたロジックを分岐させても良い
        $policyMethod = $this->mapPermissionToPolicyMethod($permission);
        
        return Gate::forUser($user)->allows($policyMethod, $articleModel);
    }

    private function mapPermissionToPolicyMethod(PermissionType $permission): string
    {
        return match($permission) {
            PermissionType::EDIT_ANY_ARTICLE => 'update',
            PermissionType::DELETE_ANY_ARTICLE => 'delete',
        };
    }
}