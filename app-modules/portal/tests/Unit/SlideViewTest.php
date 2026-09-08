<?php

declare(strict_types=1);

use He4rt\Community\Retrospective\Enums\CoverKind;
use He4rt\Portal\Retrospective\SlideView;

it('mapeia o kind para o partial do deck, com underscore virando hífen', function (): void {
    expect(SlideView::kind('discord.new_members'))->toBe('portal::retro.slides.discord.new-members')
        ->and(SlideView::kind('github.repos'))->toBe('portal::retro.slides.github.repos');
});

it('resolve o arquivo do partial relativo à raiz do projeto', function (): void {
    expect(SlideView::path(SlideView::kind('discord.voice_board')))
        ->toBe('app-modules/portal/resources/views/retro/slides/discord/voice-board.blade.php');
});

it('resolve os arquivos das capas e do fecho', function (): void {
    expect(SlideView::path(SlideView::cover()))
        ->toBe('app-modules/portal/resources/views/components/retro/slides/cover/retrospective.blade.php')
        ->and(SlideView::path(SlideView::cover(CoverKind::Onboarding)))
        ->toBe('app-modules/portal/resources/views/components/retro/slides/cover/onboarding.blade.php')
        ->and(SlideView::path(SlideView::closing()))
        ->toBe('app-modules/portal/resources/views/components/retro/slides/closing.blade.php');
});

it('devolve null para uma view que não existe em vez de um caminho que mente', function (): void {
    expect(SlideView::path(SlideView::kind('github.slide_aposentado')))->toBeNull();
});

it('todo kind emitido pelas fontes registradas tem partial', function (): void {
    $kinds = [];

    foreach (app()->tagged('retrospective.source') as $source) {
        foreach ($source->slideCatalog() as $descriptor) {
            $kinds[] = $descriptor->kind;
        }
    }

    expect($kinds)->not->toBeEmpty();

    foreach ($kinds as $kind) {
        expect(SlideView::path(SlideView::kind($kind)))->not->toBeNull("o kind {$kind} não tem partial");
    }
});
