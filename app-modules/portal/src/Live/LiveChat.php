<?php

declare(strict_types=1);

namespace He4rt\Portal\Live;

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Chat\Actions\SendChatMessage;
use He4rt\Live\Chat\DTOs\ChatMessageData;
use He4rt\Live\Chat\Exceptions\ChatMessageRejected;
use He4rt\Live\Models\Live;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class LiveChat extends Component
{
    #[Locked]
    public string $liveId;

    public string $draft = '';

    public function send(SendChatMessage $sendChatMessage): void
    {
        $this->validate(['draft' => ['required', 'string', 'min:1', 'max:500']]);

        $user = auth()->user();
        $live = Live::query()->findOrFail($this->liveId);

        abort_unless($user instanceof User, 403);

        try {
            $sendChatMessage->execute($user, $live, mb_trim($this->draft));
        } catch (ChatMessageRejected $chatMessageRejected) {
            $this->addError('draft', $chatMessageRejected->getMessage());

            return;
        }

        $this->draft = '';
    }

    public function render(): View
    {
        $history = Message::query()
            ->where('channel_id', $this->liveId)
            ->latest('sent_at')
            ->limit(50)
            ->with('provider.user')
            ->get()
            ->reverse()
            ->values()
            ->map(fn (Message $message): array => ChatMessageData::fromMessage(
                $message,
                $message->provider->user ?? new User(['name' => 'Membro', 'username' => 'membro']),
            )->toArray());

        return view('portal::live-chat', ['history' => $history]);
    }
}
