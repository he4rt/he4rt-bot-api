<?php

declare(strict_types=1);

namespace Database\Seeders;

use He4rt\Activity\Message\Models\Message;
use He4rt\Community\Meeting\Models\Meeting;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Season\Models\Season;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\Database\Seeders\RolesSeeder;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        $admin = User::factory()
            ->create([
                'username' => 'danielhe4rt',
                'name' => 'Daniel Reis',
                'email' => 'admin@admin.com',
                'password' => Hash::make('admin'),
            ]);

        $admin->assignRole(UserRole::SuperAdmin);

        Character::factory()
            ->recycle($admin)
            ->createOne();

        Season::factory()
            ->create([
                'name' => 'Season 1',
                'started_at' => now()->subMonth(),
                'ended_at' => now()->addMonth(),
            ]);

        ExternalIdentity::factory()
            ->recycle($admin)
            ->count(2)
            ->create(['email' => $admin->email]);

        Meeting::factory()
            ->count(2)
            ->create();

        Message::factory()
            ->count(2)
            ->create();
    }
}
