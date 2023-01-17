<?php

namespace Tests\Character\Character\Domain\Entities;

use Heart\Character\Domain\Entities\CharacterEntity;
use PHPUnit\Framework\TestCase;

class CharacterEntityTest extends TestCase
{
    /**
     * @dataProvider characterProvider
     */
    public function testInstanceCharacterEntityTest($id, $userId, $reputation, $experience, $claimedAt, $expectedLevel)
    {
        $characterEntity = new CharacterEntity($id, $reputation, $userId, $experience, $claimedAt);

        self::assertEquals($expectedLevel, $characterEntity->getLevel());
        self::assertInstanceOf(CharacterEntity::class, $characterEntity);
    }

    public function characterProvider(): array
    {
        return [
            [1, 1, 1, 548, '2023-01-14 00:26:25', 4],
            [1, 1, 1, 89, '2023-01-14 00:26:25', 1],
            [1, 1, 1, 287, '2023-01-14 00:26:25', 3]
        ];
    }

    /**
     * @dataProvider makeCharacterProvider
     */
    public function testMakeCharacter($payload, $expectedLevel): void
    {
        $characterEntity = CharacterEntity::make($payload);

        self::assertEquals($expectedLevel, $characterEntity->getLevel());
        self::assertInstanceOf(CharacterEntity::class, $characterEntity);
    }

    public function makeCharacterProvider(): array
    {
        return [
            [['id' => 1, 'user_id' => 1, 'reputation' => 1, 'experience' => 548, 'daily_bonus_claimed_at' => '2023-01-14 00:26:25'], 4],
            [['id' => 1, 'user_id' => 1, 'reputation' => 1, 'experience' => 98, 'daily_bonus_claimed_at' => '2023-01-14 00:26:25'], 2],
        ];
    }
}
