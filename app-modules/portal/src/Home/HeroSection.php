<?php

declare(strict_types=1);

namespace He4rt\Portal\Home;

use He4rt\Activity\Message\Models\Message;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class HeroSection extends Component
{
    #[Computed]
    public function usersCount(): int
    {
        return User::query()->count();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function avatars(): array
    {
        if (!app()->isProduction()) {
            return $this->fetchAvatars();
        }

        return Cache::remember('portal:hero:avatars', now()->addHour(), fn () => $this->fetchAvatars());
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function terminalStats(): array
    {
        if (!app()->isProduction()) {
            return $this->fetchTerminalStats();
        }

        return Cache::remember('portal:hero:terminal', now()->addHour(), fn () => $this->fetchTerminalStats());
    }

    public function render(): View
    {
        return view('portal::sections.hero');
    }

    /**
     * @return array<int, string>
     */
    private function fetchAvatars(): array
    {
        $activeDiscordIdentityIds = Message::query()
            ->where('sent_at', '>=', now()->subDays(30))
            ->select('external_identity_id')
            ->groupBy('external_identity_id')
            ->havingRaw('COUNT(*) > 20')
            ->pluck('external_identity_id');

        if ($activeDiscordIdentityIds->isEmpty()) {
            return [];
        }

        $activeUserIds = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->whereIn('id', $activeDiscordIdentityIds)
            ->pluck('model_id');

        if ($activeUserIds->isEmpty()) {
            return [];
        }

        return ExternalIdentity::query()
            ->where('provider', IdentityProvider::GitHub)
            ->whereIn('model_id', $activeUserIds)
            ->whereNotNull('external_account_id')
            ->whereRaw("external_account_id ~ '^[0-9]+$'")
            ->inRandomOrder()
            ->limit(10)
            ->pluck('external_account_id')
            ->map(fn (string $id) => sprintf('https://avatars.githubusercontent.com/u/%s?v=4', $id))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchTerminalStats(): array
    {
        $totalMessages = Message::query()->count();
        $totalXp = Character::query()->sum('experience');

        return [
            'members' => number_format(User::query()->count(), thousands_separator: '.'),
            'messages' => number_format($totalMessages, thousands_separator: '.'),
            'xp' => number_format((int) $totalXp, thousands_separator: '.'),
        ];
    }
}
