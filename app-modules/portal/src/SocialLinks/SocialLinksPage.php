<?php

declare(strict_types=1);

namespace He4rt\Portal\SocialLinks;

use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(name: 'portal::components.layouts.app')]
final class SocialLinksPage extends Component
{
    /**
     * @return list<SocialLink>
     */
    public static function links(): array
    {
        $links = [];

        foreach (config()->array('he4rt.social_media') as $key => $link) {
            if (!is_array($link) || !is_string($key)) {
                continue;
            }

            $links[] = new SocialLink(
                key: $key,
                label: self::field($link, 'label'),
                url: self::field($link, 'url'),
                icon: self::field($link, 'icon'),
                accent: self::field($link, 'accent'),
                accentDark: isset($link['accent_dark']) ? self::field($link, 'accent_dark') : null,
            );
        }

        return $links;
    }

    public function render(): View
    {
        return view('portal::social-links', [
            'links' => self::links(),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $link
     */
    private static function field(array $link, string $key): string
    {
        $value = $link[$key] ?? null;

        throw_unless(is_string($value), InvalidArgumentException::class, sprintf("Configuração he4rt.social_media inválida: '%s' deve ser uma string.", $key));

        return $value;
    }
}
