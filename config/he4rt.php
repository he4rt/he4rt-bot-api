<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;

return [
    'season' => [
        'id' => (int) env('HE4RT_SEASON_ID', 2),
        'minimum_level_for_retro' => env('HE4RT_SEASON_MIN_LEVEL', 3),
    ],
    'server_key' => env('HE4RT_BOT_SECRET', 'he4rt'),
    'admins' => env('HE4RT_ADMINS_USERNAMES', ''),
    'username_cooldown_days' => (int) env('HE4RT_USERNAME_COOLDOWN_DAYS', 7),

    /*
     * Quando a He4rt começou. Data única para qualquer lugar que precise contar a
     * idade da comunidade — hoje a seção "A He4rt" da retrospectiva. A primeira
     * temporada ('seasons') herda o mesmo marco, mas dizer a idade a partir de um
     * array de temporadas seria derivar identidade de calendário.
     */
    'founded_at' => '2018-08-01',

    'features' => [
        'timeline_pin' => env('HE4RT_FEATURE_TIMELINE_PIN', default: false),
    ],

    'discord' => [
        'token' => env('DISCORD_TOKEN'),
        'voice_xp_interval' => (int) env('HE4RT_DISCORD_VOICE_XP_INTERVAL', 1_200),
        'levelup_channel_id' => env('HE4RT_DISCORD_LEVELUP_CHANNEL', '552332704381927424'),
        'guild_id' => env('HE4RT_DISCORD_GUILD', '452926217558163456'),
        'moderation' => [
            'admin_role_ids' => explode(',', (string) env('HE4RT_DISCORD_MODERATION_ADMIN_ROLES', '547549573959385098,547543574091268118,547549400164073472')),
            'mod_role_ids' => explode(',', (string) env('HE4RT_DISCORD_MODERATION_MOD_ROLES', '547549463942791181')),
            'mod_channel_id' => env('HE4RT_DISCORD_MODERATION_CHANNEL', '1095115912820043829'),
        ],
    ],
    'channels' => [
        'commands' => '542840741588762637',
        'dynamic_voice_category' => env('HE4RT_DYNAMIC_VOICE_CATEGORY'),
    ],
    'seasons' => [
        [
            'name' => 'Legado 4Y',
            'description' => 'A primeira temporada que dura desde o inicio da He4rt Developers.',
            'starts_at' => Date::parse('2018-08-01'),
            'ends_at' => Date::parse('2022-12-31'),
            'messages_count' => 0,
            'participants_count' => 0,
            'meetings_count' => 0,
            'badges_count' => 0,
        ],
        [
            'name' => 'He4rt Shippuden',
            'description' => 'Segunda temporada chegando foda memo dps nois acha um nome melhor pra isso aqui.',
            'starts_at' => Date::parse('2023-01-01'),
            'ends_at' => Date::parse('2023-12-31'),
            'messages_count' => 0,
            'participants_count' => 0,
            'meetings_count' => 0,
            'badges_count' => 0,
        ],
    ],

    /*
     * Fonte única das redes sociais da He4rt. Alimenta a página /redes
     * (He4rt\Portal\Livewire\SocialLinksPage) e qualquer outro lugar que
     * precise dos links. `accent_dark` é opcional (cai no `accent` quando ausente).
     */
    'social_media' => [
        'discord' => ['label' => 'Discord', 'url' => 'https://discord.gg/invite/he4rt', 'icon' => 'fab-discord', 'accent' => '#5865F2'],
        'twitter' => ['label' => 'X (Twitter)', 'url' => 'https://x.com/He4rtDevs', 'icon' => 'fab-x-twitter', 'accent' => '#0F172A', 'accent_dark' => '#FFFFFF'],
        'linkedin' => ['label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/he4rt/', 'icon' => 'fab-linkedin', 'accent' => '#0A66C2'],
        'whatsapp' => ['label' => 'WhatsApp', 'url' => 'https://chat.whatsapp.com/EBKjYxIodpe1x5LLExbTzK', 'icon' => 'fab-whatsapp', 'accent' => '#25D366'],
        'instagram' => ['label' => 'Instagram', 'url' => 'https://www.instagram.com/heartdevs/', 'icon' => 'fab-instagram', 'accent' => '#E4405F'],
        'github' => ['label' => 'GitHub', 'url' => 'https://github.com/he4rt', 'icon' => 'fab-github', 'accent' => '#111827', 'accent_dark' => '#FFFFFF'],
        'loja' => ['label' => 'Loja', 'url' => 'https://loja.heartdevs.com/he4rt/', 'icon' => 'heroicon-s-shopping-bag', 'accent' => '#111827', 'accent_dark' => '#FFFFFF'],
    ],

    /*
     * Metadados do <head> público, consumidos por App\Support\Seo\SiteHead
     * para montar os defaults do laravel/head (title, description, Open Graph,
     * X/Twitter cards e o JSON-LD de Organization/WebSite).
     *
     * `og_image` precisa ser 1200x630 para render em card grande; trocar o
     * arquivo exige atualizar `og_image_width`/`og_image_height` junto.
     */
    'seo' => [
        'description' => 'Uma comunidade de desenvolvedores dedicada a ajudar iniciantes a se tornarem profissionais através de projetos, mentorias e networking.',
        'og_image' => 'images/og-default.png',
        'og_image_width' => 1_200,
        'og_image_height' => 630,
        'og_image_alt' => 'He4rt Developers — desenvolva seu potencial na comunidade',
        'twitter_handle' => '@He4rtDevs',
        'theme_color' => '#782bf1',
    ],
];
