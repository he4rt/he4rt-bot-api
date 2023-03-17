<?php

namespace Tests\Unit\Message\Application;

use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Character\Domain\Actions\IncrementExperience;
use Heart\Meeting\Application\AttendMeeting;
use Heart\Message\Application\NewMessage;
use Heart\Message\Domain\Actions\PersistMessage;
use Heart\Message\Domain\DTO\NewMessageDTO;
use Heart\Provider\Application\FindProvider;
use Heart\Provider\Application\NewAccountByProvider;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Illuminate\Support\Facades\Cache;
use Mockery as m;
use Tests\TestCase;

class NewMessageTest extends TestCase
{
    /** @dataProvider dataProvider */
    public function testNewMessage(string $provider, array $payload)
    {
        Cache::tags(['meetings'])->put('current-meeting', 'é o canhas');
        $findProviderStub = m::mock(FindProvider::class);
        $findCharacterStub = m::mock(FindCharacterIdByUserId::class);
        $characterExperienceStub = m::mock(IncrementExperience::class);
        $persistMessageStub = m::mock(PersistMessage::class);
        $attendMeetingStub = m::mock(AttendMeeting::class);
        $newUserStub = m::mock(NewAccountByProvider::class);

        $obtainedExperience = 1;
        $providerEntityMock = new ProviderEntity(
            1,
            'id-user-foda',
            'twitch',
            '12312312',
            'email@foda.com'
        );

        $findProviderStub
            ->shouldReceive('handle')
            ->with($provider, $payload['provider_id'])
            ->andReturn($providerEntityMock);

        $findCharacterStub
            ->shouldReceive('handle')
            ->once()
            ->with($providerEntityMock->userId)
            ->andReturn('id-character-foda');

        $characterExperienceStub
            ->shouldReceive('incrementByTextMessage')
            ->once()
            ->with('id-character-foda', $payload['content'])
            ->andReturn($obtainedExperience);

        $persistMessageStub
            ->shouldReceive('handle')
            ->once()
            ->with(m::type(NewMessageDTO::class), $obtainedExperience, $providerEntityMock->id);

        $attendMeetingStub
            ->shouldReceive('handle')
            ->once()
            ->with($providerEntityMock->userId);

        $action = new NewMessage(
            $persistMessageStub,
            $findProviderStub,
            $findCharacterStub,
            $characterExperienceStub,
            $attendMeetingStub,
            $newUserStub
        );

        $action->persist($payload);
        Cache::flush();
    }

    public static function dataProvider(): array
    {
        return [
            'twitch #1' => [
                'provider' => 'twitch',
                'payload' => [
                    'provider' => 'twitch',
                    'provider_id' => '1234',
                    'provider_message_id' => '78781237',
                    'channel_id' => '31231267312',
                    'content' => 'deixa o sub',
                    'sent_at' => '2023-01-18 22:36:32',
                ],
            ],
        ];
    }
}
