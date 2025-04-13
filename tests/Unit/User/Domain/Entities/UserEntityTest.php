<?php

namespace Tests\Unit\User\Domain\Entities;

use Heart\User\Domain\Entities\UserEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserEntityTest extends TestCase
{

    #[DataProvider('validUserPayloads')]
    public function testCanCreateEntity(array $userPayload)
    {
        $user = UserEntity::fromArray($userPayload);

        $this->assertInstanceOf(UserEntity::class, $user);
    }

    public static function validUserPayloads(): array
    {
        return [
            [[
                'id' => 123,
                'name' => 'Luis Alberto Suárez',
                'username' => 'brabo3k',
                'is_donator' => true,
            ]],
            [[
                'id' => 1,
                'name' => 'Diego Souza',
                'username' => 'brabo4k',
                'is_donator' => false,
            ]],
        ];
    }
}
