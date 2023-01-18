<?php

namespace Heart\Message\Domain\Entities;

use DateTime;

class MessageEntity
{
    public string $providerId;
    public string $providerMessageId;
    public int $seasonId;
    public string $channelId;
    public string $content;
    public DateTime $sentAt;
    public int $obtainedExperience;

    public function __construct(
        string $providerId,
        string $providerMessageId,
        int    $seasonId,
        string $channelId,
        string $content,
        string $sentAt,
        int    $obtainedExperience,
    )
    {
        $this->providerId = $providerId;
        $this->providerMessageId = $providerMessageId;
        $this->seasonId = $seasonId;
        $this->channelId = $channelId;
        $this->content = $content;
        $this->sentAt = new DateTime($sentAt);
        $this->obtainedExperience = $obtainedExperience;
    }
}
