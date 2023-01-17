<?php

namespace Tests\Unit\User\Domain\Entities;

use Heart\User\Domain\Entities\UserEntity;
use Heart\User\Domain\Exceptions\UserEntityException;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserEntityTest extends TestCase
{
    /**
     * @test
     * @dataProvider validUserPayloads
     */
    public function canCreateEntity(array $userPayload)
    {
        $user = UserEntity::fromArray($userPayload);

        $this->assertInstanceOf(UserEntity::class, $user);
    }

    /**
     * @test
     * @dataProvider invalidUserPayloads
     */
    public function cannotCreateUserEntityWithInvalidPayload(array $payload)
    {
        $this->expectException(UserEntityException::class);

        UserEntity::fromArray($payload);
    }

    private function validUserPayloads(): array
    {
        return [
            [[
                'id' => 123,
                'name' => 'Luis Alberto Suárez',
                'isDonator' => true,
            ]],
            [[
                'id' => 1,
                'name' => 'Diego Souza',
                'isDonator' => false,
            ]],
        ];
    }

    private function invalidUserPayloads(): array
    {
        return [
            [[
                'uuid' => Str::uuid()->toString(),
                'name' => 'D\'Alessandro',
                'isDonator' => false
            ]],
            [[
                'id' => 2,
                'first_name' => 'Taison',
                'isDonator' => true,

            ]],
            [[
                'id' => 3,
                'name' => 'Edenílson',
                'donator' => true,
            ]],
        ];
    }
}
