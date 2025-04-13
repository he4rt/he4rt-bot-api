<?php

namespace Tests\Unit\Character\Domain\Entities;

use Heart\Character\Domain\Entities\ReputationEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReputationEntityTest extends TestCase
{
    #[DataProvider('reputationDataProvider')]
    public function testReputation(int $points, string $operation, int $expectedPoints, string $expectedBadge): void
    {
        $reputation = new ReputationEntity($points);
        $reputation->handleReputation($operation);

        $this->assertEquals($expectedBadge, $reputation->getBadge());
        $this->assertEquals($expectedPoints, $reputation->getPoints());
    }

    public static function reputationDataProvider(): array
    {
        return [
            'increment #1' => ['points' => 5, 'operation' => 'increment', 'expectedPoints' => 6, 'expectedBadge' => 'Lenda'],
            'increment #2' => ['points' => 7, 'operation' => 'increment', 'expectedPoints' => 8, 'expectedBadge' => 'Lenda'],
            'increment #3' => ['points' => -2, 'operation' => 'increment', 'expectedPoints' => -1, 'expectedBadge' => 'Vacilão'],
            'decrement #1' => ['points' => -3, 'operation' => 'decrement', 'expectedPoints' => -4, 'expectedBadge' => 'Vacilão'],
            'decrement #2' => ['points' => 1, 'operation' => 'decrement', 'expectedPoints' => 0, 'expectedBadge' => 'Sem informações'],
            'decrement #3' => ['points' => 0, 'operation' => 'decrement', 'expectedPoints' => -1, 'expectedBadge' => 'Vacilão'],
        ];
    }
}
