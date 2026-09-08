<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Seeders;

use He4rt\Identity\Authorization\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolesSeeder extends Seeder
{
    public function run(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, UserRole::GUARD);
        }
    }
}
