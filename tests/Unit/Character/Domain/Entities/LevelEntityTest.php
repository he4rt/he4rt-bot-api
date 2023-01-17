<?php

namespace Tests\Unit\Character\Domain\Entities;

use Heart\Character\Domain\Entities\LevelEntity;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class LevelEntityTest extends TestCase
{
    /** @dataProvider levelProvider */
    public function testLevelEntity(int $experience, int $expectedLevel)
    {
        $levelEntity = new LevelEntity($experience);
        $this->assertEquals($expectedLevel, $levelEntity->getLevel());
    }

    public function testLevelingByChatMessage()
    {
        $level = $this->entityWithLevel(1);
        foreach (range(1,12) as $month) {
            dump('month ' . $month);
            foreach (range(1, 30) as $day) {
                dump('day ' . $day);
                foreach (range(0, 20) as $message) {
                    $messageSent = Str::random(25);
                    dump('actual level: ' . $level->getLevel());
                    dump('actual experience: ' . $level->getExperience());
                    $experienceGenerated = $level->generateExperience($messageSent);
                    dump('experience earned: ' . $experienceGenerated);
                }
            }
        }

        $this->assertEquals(10, $level->getLevel());
    }

    public function levelProvider(): array
    {
        return [
            [0, 0],
            [88, 1],
            [386, 3],
            [15000, 20],
            [23430, 30]
        ];
    }

    private function entityWithLevel(int $level): LevelEntity
    {
        $levelArray = [
            1 => 1,
            10 => 4895,
        ];

        return new LevelEntity($levelArray[$level]);
    }
}
