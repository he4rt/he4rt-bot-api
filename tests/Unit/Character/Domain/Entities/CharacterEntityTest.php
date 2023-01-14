<?php

namespace Tests\Character\Character\Domain\Entities;

use DateTime;
use Heart\Character\Domain\Entities\CharacterEntity;
use Heart\Character\Domain\Entities\LevelEntity;
use Heart\Character\Domain\Exceptions\LevelException;
use PHPUnit\Framework\TestCase;

class CharacterEntityTest extends TestCase
{
    /**
     * @dataProvider characterProvider
     */
    public function testCharacterCreateEntityTest($id, $userId, $experience, $claimedAt, $expectedLevel)
    {
        $characterEntity = new CharacterEntity($id, $userId, $experience, $claimedAt);

        self::assertInstanceOf(LevelEntity::class, $characterEntity->level);
        self::assertEquals($expectedLevel, $characterEntity->level->getLevel());
    }

    public function characterProvider()
    {
        return [
            [1,1,548, '2023-01-14 00:26:25', 4],
            [1,1,89, '2023-01-14 00:26:25', 1],
            [1,1,287, '2023-01-14 00:26:25', 3]
        ];
    }

    /**
     * @dataProvider makeCharacterProvider
     */
    public function testMakeCharacter($payload, $expectedLevel)
    {
        $characterEntity = CharacterEntity::make($payload);
        self::assertEquals($expectedLevel, $characterEntity->level->getLevel());
    }


    public function makeCharacterProvider()
    {
        return [
            [['id' => 1, 'user_id' => 1, 'experience' => 548, 'daily_bonus_claimed_at' => '2023-01-14 00:26:25'], 4],
        ];
    }
}
