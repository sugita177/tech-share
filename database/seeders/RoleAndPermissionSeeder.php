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
        // 既存のキャッシュをクリア（権限変更時は必須）
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. 権限の作成
        Permission::create(['name' => PermissionType::EDIT_ANY_ARTICLE->value]);
        Permission::create(['name' => PermissionType::DELETE_ANY_ARTICLE->value]);

        // 2. ロールの作成と権限付与
        $admin = Role::create(['name' => RoleType::ADMIN->value]);
        $admin->givePermissionTo([
            PermissionType::EDIT_ANY_ARTICLE->value,
            PermissionType::DELETE_ANY_ARTICLE->value,
        ]);

        // 一般ユーザーロール（権限なし）
        Role::create(['name' => RoleType::USER->value]);
    }
}