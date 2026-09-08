<?php

declare(strict_types=1);

namespace He4rt\Portal\Retrospective;

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\Actions\ComposeDeck;
use He4rt\Community\Retrospective\Contracts\PersonDirectory;
use He4rt\Community\Retrospective\DTOs\PersonIdentity;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\CoverKind;
use He4rt\Community\Retrospective\Models\Retrospective;

/**
 * Monta as props da view do deck a partir de uma edição. Ponto único: a página
 * pública, o preview e o builder do painel passam por aqui, então não existe um
 * caminho de render que possa divergir do outro (ADR-0002).
 *
 * `live` é o modo rascunho: coleta as fontes na hora em vez de ler o snapshot
 * congelado, para o operador ver o que SERÁ publicado.
 */
final class DeckPresentation
{
    /**
     * @return array{sources: list<SourceResult>, promotions: list<PromotionSlide>, since: CarbonImmutable, until: CarbonImmutable, coverKind: CoverKind, edition: int|null, hosts: list<PersonIdentity>, coverTitle: string|null, coverIntro: string|null, closingText: string|null, stateKey: string}
     */
    public static function for(?Retrospective $retrospective, bool $live = false): array
    {
        if (!$retrospective instanceof Retrospective) {
            return self::blank();
        }

        return self::fromSnapshot($retrospective, self::snapshotFor($retrospective, $live));
    }

    /**
     * As mesmas props, a partir de um snapshot que quem chama já tem em mãos.
     *
     * Existe para o Deck Builder compor o deck e montar o filmstrip com UMA
     * coleta: em rascunho o snapshot é coletado ao vivo, e resolvê-lo duas vezes
     * por render pagaria a conta duas vezes para mostrar a mesma coisa.
     *
     * @return array{
     *     sources: list<SourceResult>,
     *     promotions: list<PromotionSlide>,
     *     since: CarbonImmutable,
     *     until: CarbonImmutable,
     *     coverKind: CoverKind,
     *     edition: int|null,
     *     hosts: list<PersonIdentity>,
     *     coverTitle: string|null,
     *     coverIntro: string|null,
     *     closingText: string|null,
     *     stateKey: string,
     * }
     */
    public static function fromSnapshot(Retrospective $retrospective, RetrospectiveSnapshot $snapshot): array
    {
        return [
            'sources' => resolve(ComposeDeck::class)->execute(
                $snapshot,
                $retrospective->deck_config,
            ),
            // Os cartões já vêm medidos de dentro do snapshot (congelados no
            // publish, ou coletados ao vivo em rascunho); aqui só se aplica a
            // curadoria de apresentação, como o ComposeDeck faz com as fontes.
            'promotions' => PromotionSection::slides($snapshot->promotions, $retrospective->deck_config),
            'since' => $retrospective->since->toImmutable(),
            'until' => $retrospective->until->toImmutable(),
            'coverKind' => $retrospective->cover_kind,
            // Edição e apresentadores só existem no onboarding. Ficam fora do
            // snapshot de propósito: são editoriais como o título, e o número
            // da edição é derivado da ordem das edições (ADR-0002).
            'edition' => $retrospective->cover_kind->isOnboarding() ? $retrospective->editionNumber() : null,
            'hosts' => $retrospective->cover_kind->isOnboarding() ? self::hosts($retrospective->deck_config->hosts) : [],
            'coverTitle' => $retrospective->cover_title,
            'coverIntro' => $retrospective->cover_intro,
            'closingText' => $retrospective->closing_text,
            // Sem filtros do visitante: o deck só muda quando a edição muda.
            'stateKey' => $retrospective->id,
        ];
    }

    /**
     * O snapshot que este deck deve usar, CRU — antes de o ComposeDeck aplicar
     * ordem e on/off. O filmstrip do builder precisa dele inteiro: uma fonte
     * desligada sai da composição, e se saísse também da tira o operador perderia
     * o botão que a religa.
     *
     * Público para haver um dono só da regra live-vs-congelado. Se o painel
     * repetisse esse `if`, um preview poderia ler o snapshot enquanto o outro
     * coletava ao vivo.
     */
    public static function snapshotFor(Retrospective $retrospective, bool $live = false): RetrospectiveSnapshot
    {
        if ($live && !$retrospective->isPublished()) {
            return resolve(CompileSnapshot::class)->execute(
                $retrospective->period(),
                $retrospective->filters(),
                $retrospective->deck_config->promotions,
            );
        }

        return $retrospective->snapshot ?? new RetrospectiveSnapshot();
    }

    /**
     * @return array{sources: list<never>, promotions: list<never>, since: CarbonImmutable, until: CarbonImmutable, coverKind: CoverKind, edition: null, hosts: list<never>, coverTitle: null, coverIntro: null, closingText: null, stateKey: string}
     */
    private static function blank(): array
    {
        return [
            'sources' => [],
            'promotions' => [],
            'since' => CarbonImmutable::now(),
            'until' => CarbonImmutable::now(),
            'coverKind' => CoverKind::Retrospective,
            'edition' => null,
            'hosts' => [],
            'coverTitle' => null,
            'coverIntro' => null,
            'closingText' => null,
            'stateKey' => 'empty',
        ];
    }

    /**
     * Resolve os apresentadores pelo mesmo diretório que resolve as pessoas da
     * tag: um lugar só decide nome e avatar, e a capa não inventa outra regra.
     * A ordem é a do operador; quem não existe mais some em vez de virar buraco.
     *
     * @param  list<string>  $userIds
     * @return list<PersonIdentity>
     */
    private static function hosts(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $people = resolve(PersonDirectory::class)->execute($userIds);

        return array_values(array_filter(array_map(
            static fn (string $userId): ?PersonIdentity => $people[$userId] ?? null,
            $userIds,
        )));
    }
}
