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


    private function validUserPayloads(): array
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
