<?php

namespace Tests\Providers\User;

use Illuminate\Support\Carbon;

class UpdateProvider
{
    public static function validPayload(array $inputs = []): array
    {
        return [
            'name' => 'daniel coração',
            'nickname' => 'danielhe4rt',
            'git' => 'https://github.com/danielhe4rt',
            'about' => 'eu faço lives codando php',
            'email' => 'daniel@he4rtdevs.com',
            'linkedin' => 'https://linkedin.com/in/danielheart',
            'is_donator' => true,
            'uf' => 'RS',
            'birthday' => '2000-01-01',
            ...$inputs
        ];
    }


}
