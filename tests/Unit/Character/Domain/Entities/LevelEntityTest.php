<?php

namespace Tests\Unit\Character\Domain\Entities;

use Heart\Character\Domain\Entities\LevelEntity;
use PHPUnit\Framework\TestCase;

class LevelEntityTest extends TestCase
{
    /**
     * @dataProvider levelProvider
     */
    public function testLevelEntity(int $experience, int $expectedLevel)
    {
        $levelEntity = new LevelEntity($experience);
        $this->assertEquals($expectedLevel, $levelEntity->level);
    }

    public function levelProvider(): array
    {
        return [
            [0, 0],
            [88, 1],
            [386, 3]
        ];
    }
}
