<?php

declare(strict_types=1);

namespace He4rt\Portal\Retrospective;

use He4rt\Community\Retrospective\Enums\CoverKind;
use Illuminate\Support\Facades\View;

/**
 * Convenção que liga um slide à sua view no portal. Dona única do mapeamento
 * kind -> partial: o deck renderiza por aqui e o Deck Builder do painel mostra o
 * mesmo caminho ao operador, então não há como um lado desviar do outro.
 *
 * O kind vira caminho direto ("discord.new_members" =>
 * retro/slides/discord/new-members.blade.php); underscore vira hífen porque o
 * kind é identificador de dado e o arquivo segue o kebab-case das views.
 */
final class SlideView
{
    public static function kind(string $kind): string
    {
        return 'portal::retro.slides.'.str_replace('_', '-', $kind);
    }

    /**
     * Uma capa por público (CoverKind): a mesma convenção dos kinds, com o caso
     * do enum virando o arquivo dentro de `slides/cover/`.
     */
    public static function cover(CoverKind $kind = CoverKind::Retrospective): string
    {
        return 'portal::components.retro.slides.cover.'.$kind->value;
    }

    public static function closing(): string
    {
        return 'portal::components.retro.slides.closing';
    }

    /**
     * Os slides da seção fixa sobre a He4rt (AboutSection). Mesma convenção dos
     * kinds — chave vira caminho —, num diretório próprio: eles não vêm de fonte
     * nenhuma, e misturá-los com `retro/slides/` sugeriria que vêm.
     */
    public static function about(string $key): string
    {
        return 'portal::components.retro.slides.about.'.str_replace('_', '-', $key);
    }

    /**
     * Caminho do arquivo relativo à raiz do projeto, pronto para abrir no editor.
     *
     * Resolvido pelo finder do Blade, não montado com string: um kind sem partial
     * (snapshot congelado antes de a view existir, ou renomeada depois) devolve
     * null em vez de um caminho que mente.
     */
    public static function path(string $view): ?string
    {
        if (!View::exists($view)) {
            return null;
        }

        $absolute = View::getFinder()->find($view);

        return str_starts_with($absolute, base_path())
            ? mb_ltrim(mb_substr($absolute, mb_strlen(base_path())), '/')
            : $absolute;
    }
}
