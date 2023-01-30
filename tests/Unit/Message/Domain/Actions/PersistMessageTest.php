<?php

namespace Tests\Unit\Message\Domain\Actions;

use Heart\Message\Domain\Actions\PersistMessage;
use Heart\Message\Domain\DTO\NewMessageDTO;
use Heart\Message\Domain\Entities\MessageEntity;
use Heart\Message\Domain\Repositories\MessageRepository;
use Heart\Provider\Domain\Enums\ProviderEnum;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Message\MessageProviderTrait;

class PersistMessageTest extends TestCase
{
    use MessageProviderTrait;

    private MessageEntity $messageEntity;

    private MockInterface $messageRepositoryStub;

    public function setUp(): void
    {
        parent::setUp();
        $this->messageRepositoryStub = m::mock(MessageRepository::class);
        $this->messageEntity = $this->validMessageEntity();
        $this->messageDTO = new NewMessageDTO(
            ProviderEnum::Discord,
            $this->messageEntity->providerId,
            $this->messageEntity->providerMessageId,
            $this->messageEntity->channelId,
            $this->messageEntity->content,
            '2023-01-24' //sentAt in string
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testPersistMessageSuccess(): void
    {
        $this->messageRepositoryStub
            ->shouldReceive('create')
            ->with($this->messageDTO, 'canhassi', $this->messageEntity->obtainedExperience)
            ->once()
            ->andReturn($this->messageEntity);

        $test = new PersistMessage($this->messageRepositoryStub);

        $test->handle($this->messageDTO, $this->messageEntity->obtainedExperience, 'canhassi');
    }
}
