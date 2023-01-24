<?php

namespace Tests\Unit\Message;

use Carbon\Carbon;
use Heart\Message\Domain\Entities\MessageEntity;

trait MessageProviderTrait
{
    public function validMessagePayload(array $fields = []): array
    {
        return [
            'id'                  => "canhassi-id",
            'provider_id'         => "é-o-canhas-id",
            'provider_message_id' => "he4rtDevelopers",
            'season_id'           => 12,
            'channel_id'          => "canal-foda",
            'content'             => "conteudo-foda",
            'sent_at'             => Carbon::now()->toString(),
            'obtained_experience' => 12,
            ...$fields
        ];
    }

    public function validMessageEntity(): MessageEntity
    {
        return MessageEntity::make($this->validMessagePayload());
    }
}
