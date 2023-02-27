<?php

namespace Tests\Unit\Meeting\Domain\Actions;

use Heart\Meeting\Domain\Actions\PaginateMeetings;
use Heart\Meeting\Domain\Entities\MeetingEntity;
use Heart\Meeting\Domain\Repositories\MeetingRepository;
use Heart\Provider\Domain\Enums\ProviderEnum;
use Heart\Shared\Domain\Paginator;
use Mockery\MockInterface;
use Mockery as m;
use Tests\TestCase;
use Tests\Unit\Meeting\MeetingProviderTrait;

class PaginateMeetingsTest extends TestCase
{
    use MeetingProviderTrait;
    private MockInterface $meetingRepositoryStub;

    private MeetingEntity $meetingEntity;

    private Paginator $paginatorStub;

    public function setUp(): void
    {
        parent::setUp();
        $this->meetingRepositoryStub = m::mock(MeetingRepository::class);
        $this->meetingEntity = $this->validMeetingEntity();
        $this->paginatorStub = m::mock(Paginator::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testPaginateMeetings(): void
    {
        $this->meetingRepositoryStub
            ->shouldReceive('paginate')
            ->with(['meetingType'])
            ->once()
            ->andReturn($this->paginatorStub);

        $test = new PaginateMeetings($this->meetingRepositoryStub);

        $test->handle(ProviderEnum::Discord);
    }
}
