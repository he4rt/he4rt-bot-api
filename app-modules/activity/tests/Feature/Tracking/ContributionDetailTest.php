<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Contracts\ContributionDetail;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Contents\Models\ContentEntry;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Support\Str;

test('PR entrega título, contexto numerado e link', function (): void {
    $contribution = GithubContribution::factory()->create([
        'repo' => 'he4rt/heartdevs.com',
        'type' => ContributionType::Pr,
        'external_ref' => 'pr:507',
        'metadata' => [
            'title' => 'fix(portal): payload malformado do dev.to',
            'url' => 'https://github.com/he4rt/heartdevs.com/pull/507',
        ],
    ]);

    expect($contribution->contributionTitle())->toBe('fix(portal): payload malformado do dev.to')
        ->and($contribution->contributionContext())->toBe('he4rt/heartdevs.com #507')
        ->and($contribution->contributionUrl())->toBe('https://github.com/he4rt/heartdevs.com/pull/507');
});

test('review empresta o número do PR que revisou', function (): void {
    $contribution = GithubContribution::factory()->create([
        'repo' => 'he4rt/heartdevs.com',
        'type' => ContributionType::Review,
        'external_ref' => 'review:3524628945',
        'target_ref' => 'pr:142',
        'metadata' => ['state' => 'APPROVED'],
    ]);

    expect($contribution->contributionTitle())->toBe('Revisão submetida')
        ->and($contribution->contributionContext())->toBe('he4rt/heartdevs.com #142')
        ->and($contribution->contributionUrl())->toBeNull();
});

test('commit não tem número nem título no lake e não inventa nenhum', function (): void {
    $contribution = GithubContribution::factory()->create([
        'repo' => 'he4rt/heartdevs.com',
        'type' => ContributionType::Commit,
        'external_ref' => 'commit:02648234f61930e4aa3f92ce1b9717ca8613f70d',
        'target_ref' => null,
        'metadata' => ['url' => 'https://github.com/he4rt/heartdevs.com/commit/0264823'],
    ]);

    expect($contribution->contributionTitle())->toBe('Commit 0264823')
        ->and($contribution->contributionContext())->toBe('he4rt/heartdevs.com');
});

test('artigo entrega título e url da própria entrada', function (): void {
    $entry = ContentEntry::factory()->create([
        'title' => 'Como eu quebrei a produção',
        'url' => 'https://dev.to/fulano/como-eu-quebrei-a-producao',
    ]);

    expect($entry->contributionTitle())->toBe('Como eu quebrei a produção')
        ->and($entry->contributionContext())->toBe('Dev.to')
        ->and($entry->contributionUrl())->toBe('https://dev.to/fulano/como-eu-quebrei-a-producao');
});

test('a interação lê o detalhe pela origem, sem guardar cópia', function (): void {
    $entry = ContentEntry::factory()->create(['title' => 'Artigo canônico']);

    $interaction = Interaction::factory()->create([
        'type' => ActivityType::Article,
        'source_type' => 'content_entry',
        'source_id' => $entry->id,
    ]);

    $detail = $interaction->fresh()->detail();

    expect($detail)->toBeInstanceOf(ContributionDetail::class)
        ->and($detail->contributionTitle())->toBe('Artigo canônico');
});

test('origem que não honra o contrato deixa a contribuição sem detalhe', function (): void {
    $interaction = Interaction::factory()->create([
        'source_type' => 'content_entry',
        'source_id' => Str::uuid()->toString(),
    ]);

    expect($interaction->fresh()->detail())->toBeNull();
});
