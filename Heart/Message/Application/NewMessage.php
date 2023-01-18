<?php

namespace Heart\Message\Application;

use Heart\Message\Domain\Actions\PersistMessage;
use Heart\Message\Domain\DTO\ProviderMessageDTO;

class NewMessage
{
    public function __construct(private readonly PersistMessage $action)
    {
    }

    public function handle(array $payload)
    {
        /**
         * 1 resolver qm é o provider 1 provider twitch/discord + provider_id
         * 2 pegar o character do provider e gerar a nova quantia de exp ganha
         * 3 persistir exp
         * 4 salvar mensagem
         */
        $providerMessage = ProviderMessageDTO::make($payload);
        $this->action->handle();
    }
}
