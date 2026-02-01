<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Enums\RoleType;
use App\Enums\PermissionType;

public function run(): void
{
    // 権限の作成
    Permission::create(['name' => PermissionType::EDIT_ANY_ARTICLE->value]);

    // ロールの作成と権限付与
    $admin = Role::create(['name' => RoleType::ADMIN->value]);
    $admin->givePermissionTo(PermissionType::EDIT_ANY_ARTICLE->value);
}