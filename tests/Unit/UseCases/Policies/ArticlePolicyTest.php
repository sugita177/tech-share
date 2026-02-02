<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\Article;
use App\Policies\ArticlePolicy;
use App\Enums\RoleType;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Policyのテストはモデル（User, Article）やSpatieの権限機能を実際に動かすため、
 * Laravelコンテナの起動(TestCase)とDBリセット(RefreshDatabase)が必要です。
 */
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // 1. 権限とロールのマスタデータを作成
    $this->seed(RoleAndPermissionSeeder::class);
    
    // 2. Policyのインスタンス化
    $this->policy = new ArticlePolicy();
});

describe('update (更新権限の判定)', function () {
    test('記事の所有者は、自分の記事を更新できる', function () {
        $user = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $user->id]);

        expect($this->policy->update($user, $article))->toBeTrue();
    });

    test('管理者ロールを持つユーザーは、他人の記事でも更新できる', function () {
        // 管理者を作成
        $admin = User::factory()->create();
        $admin->assignRole(RoleType::ADMIN->value);
        
        // 他人の記事を作成
        $otherUser = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $otherUser->id]);

        expect($this->policy->update($admin, $article))->toBeTrue();
    });

    test('権限のない第三者は、他人の記事を更新できない', function () {
        $stranger = User::factory()->create();
        $owner = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $owner->id]);

        expect($this->policy->update($stranger, $article))->toBeFalse();
    });
});

describe('delete (削除権限の判定)', function () {
    test('記事の所有者は、自分の記事を削除できる', function () {
        $user = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $user->id]);

        expect($this->policy->delete($user, $article))->toBeTrue();
    });

    test('管理者ロールを持つユーザーは、他人の記事でも削除できる', function () {
        $admin = User::factory()->create();
        $admin->assignRole(RoleType::ADMIN->value);
        
        $article = Article::factory()->create();

        expect($this->policy->delete($admin, $article))->toBeTrue();
    });

    test('権限のない第三者は、他人の記事を削除できない', function () {
        $stranger = User::factory()->create();
        $article = Article::factory()->create();

        expect($this->policy->delete($stranger, $article))->toBeFalse();
    });
});