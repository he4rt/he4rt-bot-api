<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\MembershipEvent;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Message\Models\MessageEmbed;
use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\IntegrationGithub\Models\GithubEventLog;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationWhatsapp\Models\WhatsAppEventLog;
use He4rt\Moderation\Audit\ModerationAuditLog;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Profile\Models\Profile;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Finder\Finder;

/**
 * O gate do guideline `06-typed-json-casts`.
 *
 * Reflete sobre todo model concreto do repo, lê o `getCasts()` real já mesclado
 * e falha em qualquer cast banido fora da allowlist.
 *
 * A allowlist é escape hatch documentado, não arquivo de despejo: cada entrada
 * carrega o motivo e deve tender a zero. Uma linha nova exige justificar por
 * que aquele JSON é genuinamente polimórfico.
 */

/**
 * Casts banidos. Strings são comparadas por igualdade; nomes de classe também
 * casam com a forma parametrizada (`AsEnumCollection:App\Enums\Foo`).
 *
 * @var list<string>
 */
const BANNED_CASTS = [
    'array',
    'json',
    'object',
    'collection',
    'encrypted:array',
    'encrypted:collection',
    'encrypted:object',
    AsArrayObject::class,
    AsCollection::class,
    AsEnumArrayObject::class,
    AsEnumCollection::class,
    AsEncryptedArrayObject::class,
    AsEncryptedCollection::class,
];

/**
 * Dívida existente, herdada de antes do guideline. Formato:
 * FQCN do model => [coluna => motivo].
 *
 * @var array<class-string<Model>, array<string, string>>
 */
const ALLOWED_LOOSE_CASTS = [
    // Lakes de webhook: o corpo é de terceiro e muda sem aviso.
    DiscordEventLog::class => [
        'payload' => 'Corpo cru do webhook do Discord — polimórfico por evento, fora do nosso controle.',
    ],
    TwitchEventLog::class => [
        'payload' => 'Corpo cru do EventSub da Twitch — polimórfico por tipo de assinatura.',
    ],
    GithubEventLog::class => [
        'payload' => 'Corpo cru do webhook do GitHub — polimórfico por evento.',
    ],
    WhatsAppEventLog::class => [
        'payload' => 'Corpo cru do webhook do WhatsApp — polimórfico por tipo de mensagem.',
    ],
    TwitchSubscription::class => [
        'condition' => 'A condição varia por tipo de assinatura EventSub; a forma é da Twitch.',
    ],
    DiscordGuild::class => [
        'features' => 'Lista de feature flags da guild, definida pelo Discord e mutável sem aviso.',
    ],

    // Dívida real: shape conhecido, ainda sem VO.
    Message::class => [
        'metadata' => 'DÍVIDA: shape conhecido. A tabela tem 2,3GB — migrar exige backfill planejado.',
    ],
    MessageEmbed::class => [
        'raw' => 'DÍVIDA: embed do Discord tem shape estável e documentado; cabe VO.',
    ],
    MembershipEvent::class => [
        'metadata' => 'DÍVIDA: shape conhecido por tipo de evento.',
    ],
    ModerationEvent::class => [
        'metadata' => 'DÍVIDA: shape conhecido.',
    ],
    CheckIn::class => [
        'payload' => 'DÍVIDA: payload de check-in é nosso, não de terceiro.',
    ],
    Enrollment::class => [
        'application_data' => 'DÍVIDA: as respostas seguem o application_schema da policy; dá para tipar em par.',
    ],
    EnrollmentPolicy::class => [
        'application_schema' => 'Schema de formulário definido por admin em runtime — genuinamente dinâmico.',
    ],
    EnrollmentTransition::class => [
        'metadata' => 'DÍVIDA: shape conhecido por transição.',
    ],
    ExternalIdentity::class => [
        'metadata' => 'DÍVIDA: já existe AsCredentials neste módulo — é o modelo a seguir.',
    ],
    GithubContribution::class => [
        'metadata' => 'DÍVIDA: shape conhecido por tipo de contribuição.',
    ],
    ModerationAuditLog::class => [
        'details' => 'DÍVIDA: varia por ação auditada; cabe VO por tipo.',
    ],
    ModerationCase::class => [
        'content_snapshot' => 'DÍVIDA: snapshot do conteúdo moderado, shape conhecido.',
        'ai_scores' => 'DÍVIDA: scores do classificador — chaves fixas, forte candidato a VO.',
    ],
    ModerationAction::class => [
        'target_platforms' => 'DÍVIDA: é uma lista de Platform; AsEnumCollection tipado resolveria.',
        'metadata' => 'DÍVIDA: shape conhecido por tipo de ação.',
        'execution_results' => 'DÍVIDA: resultado por plataforma, shape conhecido.',
    ],
    Profile::class => [
        'social_links' => 'DÍVIDA: handles por rede — chaves fixas, candidato óbvio a VO.',
    ],
];

/**
 * Todo model concreto de `app/Models` e `app-modules/{module}/src`.
 *
 * @return list<class-string<Model>>
 */
function concreteModels(): array
{
    $roots = array_filter([
        base_path('app/Models'),
        ...glob(base_path('app-modules/*/src')) ?: [],
    ], is_dir(...));

    if ($roots === []) {
        return [];
    }

    $models = [];

    foreach (Finder::create()->files()->in($roots)->name('*.php') as $file) {
        $source = $file->getContents();

        if (!preg_match('/^namespace\s+([^;]+);/m', $source, $ns)) {
            continue;
        }

        if (!preg_match('/^(?:final\s+|abstract\s+)*class\s+(\w+)/m', $source, $cls)) {
            continue;
        }

        $fqcn = mb_trim($ns[1]).'\\'.$cls[1];

        if (!class_exists($fqcn)) {
            continue;
        }

        $reflection = new ReflectionClass($fqcn);

        if ($reflection->isAbstract() || !$reflection->isSubclassOf(Model::class)) {
            continue;
        }

        $models[] = $fqcn;
    }

    sort($models);

    return $models;
}

/** O cast está na lista de banidos? Cobre a forma parametrizada `Classe:arg`. */
function isBannedCast(string $cast): bool
{
    $bare = str_contains($cast, ':') ? mb_strstr($cast, ':', before_needle: true) : $cast;

    return in_array($cast, BANNED_CASTS, strict: true) || in_array($bare, BANNED_CASTS, strict: true);
}

it('encontra models para inspecionar', function (): void {
    // Sem esta guarda, um erro de caminho faria o teste passar sem olhar nada.
    expect(concreteModels())->not->toBeEmpty();
});

it('não permite cast de array solto fora da allowlist', function (): void {
    $violations = [];

    foreach (concreteModels() as $model) {
        $allowed = ALLOWED_LOOSE_CASTS[$model] ?? [];

        foreach ((new $model)->getCasts() as $attribute => $cast) {
            if (!is_string($cast) || !isBannedCast($cast)) {
                continue;
            }

            if (array_key_exists($attribute, $allowed)) {
                continue;
            }

            $violations[] = sprintf('%s::$%s => %s', $model, $attribute, $cast);
        }
    }

    expect($violations)->toBe([], sprintf(
        "Cast de array solto sem allowlist:\n  %s\n\n"
        .'Crie um value object e um cast tipado (veja AsCredentials no módulo identity, '
            ."ou AsTagList/AsUtmParameters no marketing).\nSe o JSON for genuinamente polimórfico, "
        .'adicione-o a ALLOWED_LOOSE_CASTS neste arquivo COM o motivo.',
        implode("\n  ", $violations),
    ));
});

it('mantém a allowlist honesta: nada listado que já foi resolvido', function (): void {
    $stale = [];

    foreach (ALLOWED_LOOSE_CASTS as $model => $attributes) {
        $casts = (new $model)->getCasts();

        foreach ($attributes as $attribute => $reason) {
            $cast = $casts[$attribute] ?? null;

            if (!is_string($cast) || !isBannedCast($cast)) {
                $stale[] = sprintf('%s::$%s', $model, $attribute);
            }
        }
    }

    expect($stale)->toBe([], sprintf(
        "Estas entradas da allowlist não são mais necessárias — remova:\n  %s",
        implode("\n  ", $stale),
    ));
});
