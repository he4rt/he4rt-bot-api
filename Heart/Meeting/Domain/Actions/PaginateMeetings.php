<?php

namespace Heart\Meeting\Domain\Actions;

use Heart\Meeting\Domain\Repositories\MeetingRepository;
use Heart\Shared\Domain\Paginator;

class PaginateMeetings
{
    public function __construct(private readonly MeetingRepository $repository)
    {
    }

    public function handle(): Paginator
    {
        return $this->repository->paginate(['meetingType']);
    }
}
