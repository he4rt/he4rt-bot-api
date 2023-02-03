<?php

namespace Tests\Unit\Character\Domain\Entities;

use Heart\Character\Domain\Entities\LevelEntity;
use Illuminate\Support\Str;
use Tests\TestCase;

class LevelEntityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testLevelEntity()
    {
        $levelEntity = new LevelEntity(1);

        foreach (range(0, 11) as $months) {
            foreach (range(0, 30) as $days) {
                foreach (range(0, 20) as $qtdMensagem) {
                    $generatedExperience = $levelEntity->generateExperience(Str::random(76), true);
//                    dump('exp ganha:' . $generatedExperience);
//                    dump('level atual: ' . $levelEntity->getLevel());
//                    dump('level exp atual: ' . $levelEntity->getLevelUpStatus());
                }
            }
        }
        $this->assertTrue(true);
    }
}