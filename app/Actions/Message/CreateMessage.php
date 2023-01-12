<?php

namespace App\Actions\Message;

use App\Actions\Event\Meeting\AttendMeeting;
use App\Exceptions\MeetingsException;
use App\Repositories\Messages\MessageRepository;

class CreateMessage
{
    private MessageRepository $messageRepository;

    private AttendMeeting $attendMeetingAction;

    public function __construct(
        MessageRepository $messageRepository,
        AttendMeeting     $attendMeetingAction
    )
    {
        $this->messageRepository = $messageRepository;
        $this->attendMeetingAction = $attendMeetingAction;
    }

    public function handle(string $discordId, string $message): void
    {
        try {
            $this->attendMeetingAction->handle(['discord_id' => $discordId]);
        } catch (MeetingsException $e) {}

        $this->messageRepository->create($discordId, $message);
    }
}
