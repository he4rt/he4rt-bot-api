<?php

namespace App\Console\Commands;

use App\Clients\DiscordClient;
use App\Models\User\User;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class UpdateAvatarCommand extends Command
{
    protected $signature = 'system:avatar';

    protected $description = 'Update users avatars';

    public function handle(DiscordClient $client)
    {
        /** @var Collection<User> $users */
        User::query()
            ->whereNull('discord_avatar_url')
            ->orderByDesc('level')
            ->chunk(200, fn(Collection $users) => $this->updateUsers($client, $users));
    }

    private function updateUsers(DiscordClient $client, Collection $users)
    {
        $users->each(fn($user) => $this->updateUser($client, $user));
    }

    private function updateUser(DiscordClient $client, User $user)
    {
        try {
            $discordUser = $client->getUser($user->discord_id);
        } catch (RequestException $exception) {
            $this->info('cooldown 5 sec');
            sleep(5);
            $discordUser = $client->getUser($user->discord_id);
        }
        $fields = ['discord_avatar_url' => $this->buildAvatarUrl($discordUser)];

        if (is_null($user->name)){
            $fields['name'] = $discordUser['username'];
        }
        if (is_null($user->nickname)){
            $fields['nickname'] = $this->buildUsername($discordUser);
        }
        $this->info(sprintf(
            'ID: %s | DiscordId: %s - %s',
            $user->id,
            $user->discord_id,
            $discordUser['username']
        ));
        $user->update($fields);
    }

    /**
     * @param $username
     * @return string
     */
    public function buildUsername(array $discordUser): string
    {
        return sprintf('%s #%s', $discordUser['username'], $discordUser['discriminator']);
    }

    private function buildAvatarUrl(array $discordUser): string
    {
        return sprintf(
            'https://cdn.discordapp.com/avatars/%s/%s.webp?size=128',
            $discordUser['id'],
            $discordUser['avatar']
        );
    }
}
