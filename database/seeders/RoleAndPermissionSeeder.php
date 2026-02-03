<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Enums\RoleType;
use App\Enums\PermissionType;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 既存のキャッシュをクリア
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. 権限の作成（firstOrCreate でガードを明示）
        // Laravel の API 開発でも、認証の基盤は 'web' ガードをベースにすることが多いです
        $permissions = [];
        foreach (PermissionType::cases() as $case) {
            $permissions[] = Permission::firstOrCreate([
                'name' => $case->value,
                'guard_name' => 'web'
            ]);
        }

        // 3. ロールの作成と権限付与
        $admin = Role::firstOrCreate([
            'name' => RoleType::ADMIN->value,
            'guard_name' => 'web'
        ]);

        // givePermissionTo ではなく syncPermissions を使う
        // これにより、既存の状態をリセットして「このリストの状態」に同期されます
        $admin->syncPermissions(PermissionType::cases());

        // 一般ユーザーロール（権限なし）
        Role::firstOrCreate([
            'name' => RoleType::USER->value,
            'guard_name' => 'web'
        ]);
    }
}