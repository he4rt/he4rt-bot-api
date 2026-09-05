<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\PromotionCard;
use He4rt\Community\Retrospective\DTOs\PromotionMetricGroup;
use He4rt\Community\Retrospective\Enums\PromotionStage;
use He4rt\Portal\Retrospective\PromotionSection;
use Illuminate\Support\Facades\Blade;

function promoCard(string $id, PromotionStage $stage, ?string $reason = 'segurou o #ajuda'): PromotionCard
{
    return new PromotionCard(
        userId: $id,
        name: 'Fulana '.$id,
        username: 'fulana'.$id,
        avatar: 'https://example.test/'.$id.'.png',
        stage: $stage,
        reason: $reason,
        groups: [
            new PromotionMetricGroup('discord', 'Discord', [new Metric('Mensagens', 8_132)]),
            new PromotionMetricGroup('github', 'GitHub', [new Metric('PRs', 47)]),
        ],
        memberSince: CarbonImmutable::parse('2021-03-10 12:00:00'),
    );
}

it('separa os cartões por estágio, um slide para cada', function (): void {
    $slides = PromotionSection::slides(
        [promoCard('u1', PromotionStage::Spotlight), promoCard('u2', PromotionStage::Promoted)],
        new DeckConfig(),
    );

    expect($slides)->toHaveCount(2)
        ->and($slides[0]->kind)->toBe(PromotionSection::SPOTLIGHT)
        ->and($slides[0]->cards[0]->userId)->toBe('u1')
        ->and($slides[1]->kind)->toBe(PromotionSection::TAG)
        ->and($slides[1]->cards[0]->userId)->toBe('u2');
});

it('não desenha slide sem ninguém e obedece o on/off da curadoria', function (): void {
    expect(PromotionSection::slides([], new DeckConfig()))->toBeEmpty();

    $slides = PromotionSection::slides(
        [promoCard('u1', PromotionStage::Spotlight), promoCard('u2', PromotionStage::Promoted)],
        new DeckConfig(hiddenSlides: [PromotionSection::TAG]),
    );

    expect($slides)->toHaveCount(1)
        ->and($slides[0]->kind)->toBe(PromotionSection::SPOTLIGHT);
});

it('conta abertura, três por pessoa e o finale só no slide da tag', function (): void {
    $slides = PromotionSection::slides(
        [
            promoCard('u1', PromotionStage::Spotlight),
            promoCard('u2', PromotionStage::Promoted),
            promoCard('u3', PromotionStage::Promoted),
        ],
        new DeckConfig(),
    );

    expect($slides[0]->steps())->toBe(0)
        ->and($slides[1]->steps())->toBe(8);
});

it('a view da tag declara os passos e marca cada faixa da revelação', function (): void {
    $cards = [promoCard('u1', PromotionStage::Promoted), promoCard('u2', PromotionStage::Promoted)];

    $html = Blade::render('@include("portal::retro.slides.he4rt.tag", ["cards" => $cards])', ['cards' => $cards]);

    // 2 + 3 por pessoa; a primeira pessoa entra no passo 1, a segunda no 4 e o
    // finale — o marcador que recua a câmera — ocupa o último passo.
    expect($html)->toContain('data-steps="8"')
        ->and($html)->toContain('data-reveal="0"')
        ->and($html)->toContain('data-reveal="1"')
        ->and($html)->toContain('data-reveal="2"')
        ->and($html)->toContain('data-reveal="3"')
        ->and($html)->toContain('data-reveal="4"')
        ->and($html)->toContain('class="promo-finale" data-reveal="7"')
        ->and($html)->toContain('Fulana u1')
        ->and($html)->toContain('8.132')
        ->and($html)->toContain('na comunidade desde')
        ->and($html)->toContain('2021');
});

it('o slide de destaques mostra todo mundo e não consome setas', function (): void {
    $cards = [promoCard('u1', PromotionStage::Spotlight), promoCard('u2', PromotionStage::Spotlight)];

    $html = Blade::render('@include("portal::retro.slides.he4rt.spotlight", ["cards" => $cards])', ['cards' => $cards]);

    expect($html)->not->toContain('data-steps')
        ->and($html)->toContain('Fulana u1')
        ->and($html)->toContain('Fulana u2');
});

it('o cartão separa as métricas por fonte e omite a frase quando não há motivo', function (): void {
    $comMotivo = Blade::render('<x-portal::retro.promotion-card :card="$card" />', ['card' => promoCard('u1', PromotionStage::Spotlight)]);
    $semMotivo = Blade::render('<x-portal::retro.promotion-card :card="$card" />', ['card' => promoCard('u2', PromotionStage::Spotlight, reason: null)]);

    expect(mb_substr_count($comMotivo, 'class="promo-source"'))->toBe(2)
        ->and($comMotivo)->toContain('Discord')
        ->and($comMotivo)->toContain('GitHub')
        ->and($comMotivo)->toContain('segurou o #ajuda')
        ->and($semMotivo)->not->toContain('promo-reason');
});
