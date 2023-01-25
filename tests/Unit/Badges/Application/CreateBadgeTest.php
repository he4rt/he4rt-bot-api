<?php

namespace Tests\Unit\Badges\Application;

use Heart\Badges\Application\CreateBadge;
use Heart\Badges\Domain\Actions\PersistBadge;
use Heart\Badges\Domain\DTOs\NewBadgeDTO;
use Heart\Badges\Domain\Entities\BadgeEntity;
use Mockery\MockInterface;
use Mockery as m;
use Mockery;
use Tests\TestCase;
use Tests\Unit\Badges\BadgeProviderTrait;

class CreateBadgeTest extends TestCase
{
    use BadgeProviderTrait;

    private MockInterface $persistBadgeStub;

    private BadgeEntity $badgeEntity;

    private array $payload;

    public function setUp(): void
    {
        parent::setUp();
        $this->persistBadgeStub = m::mock(PersistBadge::class);
        $this->badgeEntity = $this->validBadgeEntity();
        $this->payload = $this->dataProvider();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testCreateBadgeApplicationSuccess(): void
    {
        $this->persistBadgeStub
            ->shouldReceive('handle')
            ->with(Mockery::type(NewBadgeDTO::class))
            ->once()
            ->andReturn($this->badgeEntity);

        $test = new CreateBadge($this->persistBadgeStub);

        $test->handle($this->payload);
    }

    private function dataProvider(): array
    {
        return [
            'provider'    => 'canhassi-provider',
            'name'        => 'canhassi',
            'description' => 'é o canhas, esqueça tudo!',
            'image_url'    => 'https://canhassi.tech',
            'redeem_code'  => 'he4rtDevelopers',
            'active'      => true
        ];
    }
}
