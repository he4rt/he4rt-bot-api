<?php

declare(strict_types=1);

namespace He4rt\Identity\Authorization\Console;

use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * Bootstrap do primeiro super admin em produção e saída para lockout: o
 * painel só concede papéis a quem já está dentro dele.
 */
class GrantSuperAdmin extends Command
{
    protected $signature = 'identity:grant-super-admin {username : Username do usuário que recebe o papel}';

    protected $description = 'Concede o papel de super admin a um usuário pelo username';

    public function handle(): int
    {
        /** @var string $username */
        $username = $this->argument('username');

        $user = User::query()->where('username', $username)->first();

        if ($user === null) {
            $this->error(sprintf('Usuário "%s" não encontrado.', $username));

            return self::FAILURE;
        }

        Role::findOrCreate(UserRole::SuperAdmin->value, UserRole::GUARD);

        $user->assignRole(UserRole::SuperAdmin);

        $this->info(sprintf('"%s" agora é %s.', $username, UserRole::SuperAdmin->getLabel()));

        return self::SUCCESS;
    }
}
