<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Article;
use App\Enums\RoleType;
use App\Domain\Enums\ArticleStatus;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. まずロールと権限のマスタを作成（依存関係の解消）
        $this->call(RoleAndPermissionSeeder::class);

        // 2. 管理者ユーザー (Admin)
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole(RoleType::ADMIN->value);

        // 3. 一般ユーザー A
        $userA = User::factory()->create([
            'name' => 'User A (Writer)',
            'email' => 'user_a@example.com',
            'password' => bcrypt('password'),
        ]);

        // User A に紐づく記事を作成
        Article::factory(5)->create([
            'user_id' => $userA->id,
            'title' => 'User Aが書いた記事',
            'status' => ArticleStatus::Published,
        ]);

        // 4. 一般ユーザー B
        $userB = User::factory()->create([
            'name' => 'User B (Reader)',
            'email' => 'user_b@example.com',
            'password' => bcrypt('password'),
        ]);

        // User B に紐づく記事を作成
        Article::factory(5)->create([
            'user_id' => $userB->id,
            'title' => 'User Bが書いた記事',
            'status' => ArticleStatus::Published,
        ]);

        dump('Seeding completed: admin@example.com, user_a@example.com, user_b@example.com');
    }
}