<?php

namespace Tests\Character\Character\Domain\Entities;

use DateTime;
use Heart\Character\Domain\Entities\CharacterEntity;
use Heart\Character\Domain\Entities\LevelEntity;
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

    public function testCharacterCreateInvalid()
    {
        $characterEntity = new CharacterEntity($id, $userId, $experience, $claimedAt);
    }


    public function characterProvider()
    {
        return [
            [1,1,548, '2023-01-14 00:26:25', 4],
            [1,1,89, '2023-01-14 00:26:25', 1],
            [1,1,287, '2023-01-14 00:26:25', 3]
        ];
    }
}
