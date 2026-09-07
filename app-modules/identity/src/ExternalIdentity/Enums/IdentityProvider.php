<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Enums;

use App\Contracts\ApiKeyClientContract;
use App\Contracts\OAuthClientContract;
use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use He4rt\Activity\Message\Contracts\MessageActivityAdapter;
use He4rt\IntegrationDevTo\ApiKey\DevToApiKeyClient;
use He4rt\IntegrationDevTo\OAuth\DevToOAuthClient;
use He4rt\IntegrationDiscord\ETL\Adapters\DiscordMessageAdapter;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthClient;
use He4rt\IntegrationGithub\OAuth\GitHubOAuthClient;
use He4rt\IntegrationTwitch\OAuth\TwitchOAuthClient;

enum IdentityProvider: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case Discord = 'discord';
    case Twitch = 'twitch';
    case DevTo = 'devto';
    case Spotify = 'spotify';
    case Steam = 'steam';
    case GitHub = 'github';
    case He4rtLives = 'he4rt-lives';
    case Xbox = 'xbox';
    case YouTube = 'youtube';
    case Twitter = 'twitter';
    case LeagueOfLegends = 'leagueoflegends';
    case RiotGames = 'riotgames';
    case BattleNet = 'battlenet';
    case EpicGames = 'epicgames';
    case PlayStation = 'playstation';
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Crunchyroll = 'crunchyroll';
    case Reddit = 'reddit';
    case Roblox = 'roblox';
    case TikTok = 'tiktok';
    case Skype = 'skype';
    case Domain = 'domain';
    case Bluesky = 'bluesky';
    case PayPal = 'paypal';
    case AmazonMusic = 'amazon-music';
    case Bungie = 'bungie';
    case Ebay = 'ebay';

    /** @return array<int, self> */
    public static function supportedProviders(): array
    {
        return [
            self::GitHub,
            self::Discord,
            self::Twitch,
            self::DevTo,
        ];
    }

    /**
     * Providers suportados agrupados pelo método de autenticação, na ordem de
     * CredentialsType::cases(). Grupos sem nenhum provider são omitidos.
     *
     * @return array<string, array<int, self>>
     */
    public static function supportedProvidersByCredentialsType(): array
    {
        $grouped = [];

        foreach (CredentialsType::cases() as $credentialsType) {
            $providers = array_values(array_filter(
                self::supportedProviders(),
                fn (self $provider): bool => $provider->getCredentialsType() === $credentialsType,
            ));

            if ($providers !== []) {
                $grouped[$credentialsType->value] = $providers;
            }
        }

        return $grouped;
    }

    /**
     * Método de autenticação que este provider usa. Um método fixo por provider.
     */
    public function getCredentialsType(): CredentialsType
    {
        return match ($this) {
            self::DevTo => CredentialsType::ApiKey,
            self::He4rtLives => CredentialsType::OAuth2,
            default => CredentialsType::OAuth2,
        };
    }

    public function getApiKeyClient(): ?ApiKeyClientContract
    {
        return match ($this) {
            self::DevTo => resolve(DevToApiKeyClient::class),
            self::He4rtLives => null,
            default => null,
        };
    }

    public function getClient(): ?OAuthClientContract
    {
        return match ($this) {
            self::Twitch => resolve(TwitchOAuthClient::class),
            self::Discord => resolve(DiscordOAuthClient::class),
            self::GitHub => resolve(GitHubOAuthClient::class),
            self::DevTo => resolve(DevToOAuthClient::class),
            self::He4rtLives => null,
            default => null,
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Discord => Color::Blue,
            self::Twitch => Color::Purple,
            self::DevTo => Color::Gray,
            self::Spotify => Color::hex('#1DB954'),
            self::Steam => Color::Slate,
            self::GitHub => Color::Neutral,
            self::He4rtLives => Color::Rose,
            self::Xbox => Color::Green,
            self::YouTube => Color::Red,
            self::Twitter => Color::Sky,
            self::LeagueOfLegends => Color::Amber,
            self::RiotGames => Color::Red,
            self::BattleNet => Color::Blue,
            self::EpicGames => Color::Zinc,
            self::PlayStation => Color::Blue,
            self::Facebook => Color::Indigo,
            self::Instagram => Color::Pink,
            self::Crunchyroll => Color::Orange,
            self::Reddit => Color::Orange,
            self::Roblox => Color::Red,
            self::TikTok => Color::Zinc,
            self::Skype => Color::Blue,
            self::Domain => Color::Teal,
            self::Bluesky => Color::Sky,
            self::PayPal => Color::Blue,
            self::AmazonMusic => Color::Orange,
            self::Bungie => Color::Yellow,
            self::Ebay => Color::Blue,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Discord => 'fab-discord',
            self::Twitch => 'fab-twitch',
            self::DevTo => 'fab-dev',
            self::Spotify => 'fab-spotify',
            self::Steam => 'fab-steam',
            self::GitHub => 'fab-github',
            self::He4rtLives => 'heroicon-o-video-camera',
            self::Xbox => 'fab-xbox',
            self::YouTube => 'fab-youtube',
            self::Twitter => 'fab-twitter',
            self::LeagueOfLegends => 'heroicon-o-sparkles',
            self::RiotGames => 'heroicon-o-shield-check',
            self::BattleNet => 'fab-battle-net',
            self::EpicGames => 'heroicon-o-sparkles',
            self::PlayStation => 'heroicon-o-sparkles',
            self::Facebook => 'fab-facebook',
            self::Instagram => 'fab-instagram',
            self::Crunchyroll => 'heroicon-o-sparkles',
            self::Reddit => 'fab-reddit',
            self::Roblox => 'heroicon-o-sparkles',
            self::TikTok => 'fab-tiktok',
            self::Skype => 'fab-skype',
            self::Domain => 'heroicon-o-globe-alt',
            self::Bluesky => 'heroicon-o-cloud',
            self::PayPal => 'fab-paypal',
            self::AmazonMusic => 'fab-amazon',
            self::Bungie => 'heroicon-o-sparkles',
            self::Ebay => 'heroicon-o-sparkles',
        };
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Discord => 'Conecte sua conta do Discord para gameficações e eventos.',
            self::Twitch => 'Conecte sua conta do Twitch para gameficações e eventos.',
            self::DevTo => 'Conecte sua conta do Dev.to para rastrear artigos e contribuições.',
            self::Spotify => 'Conecte sua conta do Spotify para rastrear músicas ouvidas.',
            self::Steam => 'Conecte sua conta da Steam para rastrear jogos.',
            self::GitHub => 'Conecte sua conta do GitHub para rastrear contribuições.',
            self::He4rtLives => 'Identidade nativa da plataforma he4rt, usada pelo chat das lives.',
            self::Xbox => 'Conecte sua conta do Xbox para gameficações.',
            self::YouTube => 'Conecte sua conta do YouTube para rastrear vídeos.',
            self::Twitter => 'Conecte sua conta do Twitter/X para interações sociais.',
            self::LeagueOfLegends => 'Conecte sua conta do League of Legends.',
            self::RiotGames => 'Conecte sua conta da Riot Games.',
            self::BattleNet => 'Conecte sua conta do Battle.net.',
            self::EpicGames => 'Conecte sua conta da Epic Games.',
            self::PlayStation => 'Conecte sua conta do PlayStation.',
            self::Facebook => 'Conecte sua conta do Facebook.',
            self::Instagram => 'Conecte sua conta do Instagram.',
            self::Crunchyroll => 'Conecte sua conta do Crunchyroll.',
            self::Reddit => 'Conecte sua conta do Reddit.',
            self::Roblox => 'Conecte sua conta do Roblox.',
            self::TikTok => 'Conecte sua conta do TikTok.',
            self::Skype => 'Conecte sua conta do Skype.',
            self::Domain => 'Conecte um domínio personalizado.',
            self::Bluesky => 'Conecte sua conta do Bluesky.',
            self::PayPal => 'Conecte sua conta do PayPal.',
            self::AmazonMusic => 'Conecte sua conta do Amazon Music.',
            self::Bungie => 'Conecte sua conta da Bungie.',
            self::Ebay => 'Conecte sua conta do eBay.',
        };
    }

    /**
     * @return array<int, string>
     */
    public function getScopes(?string $panel = null): array
    {
        $scopes = match ($this) {
            self::Discord => config('services.discord.scopes'),
            self::Twitch => config('services.twitch.scopes.'.($panel ?? 'app'), config('services.twitch.scopes.app')),
            self::He4rtLives => '',
            default => '',
        };

        return $scopes ? explode(' ', $scopes) : [];
    }

    public function isEnabled(): bool
    {
        return config(sprintf('services.%s.enabled', $this->value));
    }

    public function getRedirectUri(): string
    {
        return route('oauth.redirect', [
            'panel' => filament()->getCurrentPanel()->getId(),
            'provider' => $this->value,
        ]);
    }

    public function getType(): IdentityType
    {
        return match ($this) {
            self::He4rtLives => IdentityType::External,
            default => IdentityType::External,
        };
    }

    public function getMessageAdapter(): ?MessageActivityAdapter
    {
        return match ($this) {
            self::Discord => resolve(DiscordMessageAdapter::class),
            self::He4rtLives => null,
            default => null,
        };
    }
}
