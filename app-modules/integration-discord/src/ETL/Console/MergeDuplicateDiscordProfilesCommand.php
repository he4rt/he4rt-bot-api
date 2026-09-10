<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Console;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\ETL\Actions\MergeDuplicateDiscordUserAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

#[Description(description: 'Reverte users duplicados criados pelo bug do discord:import-profiles (re-aponta FKs e identities, deleta dups)')]
#[Signature(signature: 'discord:merge-duplicate-profiles
                            {--tenant=he4rt : Slug do tenant alvo (ignorado se --pairs-file for usado)}
                            {--from-date=2026-05-01 : Cutoff: users criados a partir desta data sao candidatos (ignorado se --pairs-file for usado)}
                            {--pairs-file= : Caminho de JSONL pre-gerado por discord:export-merge-pairs (fonte de verdade)}
                            {--dry-run : Nao executa, so reporta plano}
                            {--limit= : Para apos N pares processados}
                            {--batch=1000 : Tamanho do batch para progress reporting}')]
class MergeDuplicateDiscordProfilesCommand extends Command
{
    public function handle(MergeDuplicateDiscordUserAction $merge): int
    {
        $pairsFile = $this->option('pairs-file');

        if ($pairsFile !== null) {
            return $this->runFromPairsFile($merge, (string) $pairsFile);
        }

        return $this->runFromHeuristic($merge);
    }

    private function runFromPairsFile(MergeDuplicateDiscordUserAction $merge, string $path): int
    {
        $absolute = str_starts_with($path, '/') ? $path : base_path($path);

        if (!is_file($absolute)) {
            error('Arquivo de pares nao encontrado: '.$absolute);

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $batch = max(1, (int) $this->option('batch'));

        $stats = ['merged' => 0, 'skipped' => 0, 'errors' => 0];

        info(sprintf('Lendo pares de %s%s...', $absolute, $dryRun ? ' [DRY-RUN]' : ''));

        $handle = fopen($absolute, 'r');
        if ($handle === false) {
            error('Nao foi possivel abrir o arquivo: '.$absolute);

            return self::FAILURE;
        }

        $line = 0;
        while (($raw = fgets($handle)) !== false) {
            $line++;

            if ($limit !== null && $stats['merged'] >= $limit) {
                break;
            }

            $raw = mb_trim($raw);
            if ($raw === '') {
                continue;
            }

            $pair = json_decode($raw, associative: true);
            if (!is_array($pair)) {
                $stats['errors']++;
                Log::error('merge-duplicate-profiles invalid JSONL line', ['line' => $line, 'raw' => $raw]);

                continue;
            }

            $oldUserId = $pair['old_user_id'] ?? null;
            $newUserId = $pair['new_user_id'] ?? null;
            $newUsername = $pair['new_username'] ?? null;

            if (!is_string($oldUserId) || !is_string($newUserId) || !is_string($newUsername)) {
                $stats['errors']++;
                Log::error('merge-duplicate-profiles missing fields in JSONL', ['line' => $line, 'pair' => $pair]);

                continue;
            }

            $oldUser = User::query()->find($oldUserId);
            $newUser = User::query()->find($newUserId);

            if (!$oldUser instanceof User || !$newUser instanceof User) {
                $stats['skipped']++;
                Log::warning('merge-duplicate-profiles user(s) not found', [
                    'line' => $line,
                    'old_user_id' => $oldUserId,
                    'new_user_id' => $newUserId,
                    'old_found' => $oldUser instanceof User,
                    'new_found' => $newUser instanceof User,
                ]);

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '  WOULD MERGE  %s (orphan, %s) <- %s (dup, %s)',
                    $oldUser->username,
                    $oldUser->created_at->toDateTimeString(),
                    $newUser->username,
                    $newUser->created_at->toDateTimeString(),
                ));
                $stats['merged']++;

                continue;
            }

            try {
                $merge->handle($oldUser, $newUser, $newUsername);
                $stats['merged']++;

                if ($stats['merged'] % $batch === 0) {
                    info(sprintf('  ... %d merged', $stats['merged']));
                }
            } catch (Throwable $e) {
                $stats['errors']++;
                Log::error('merge-duplicate-profiles failed', [
                    'line' => $line,
                    'old_user_id' => $oldUserId,
                    'new_user_id' => $newUserId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        fclose($handle);

        $this->newLine();
        table(
            headers: ['Metrica', 'Valor'],
            rows: [
                ['Merged', number_format($stats['merged'])],
                ['Skipped (user nao encontrado)', number_format($stats['skipped'])],
                ['Erros', number_format($stats['errors'])],
                ['Linhas processadas', number_format($line)],
            ],
        );

        info($dryRun ? 'Dry-run finalizado.' : 'Merge finalizado.');

        return self::SUCCESS;
    }

    private function runFromHeuristic(MergeDuplicateDiscordUserAction $merge): int
    {
        $fromDate = (string) $this->option('from-date');
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $batch = max(1, (int) $this->option('batch'));

        $stats = ['merged' => 0, 'no_match' => 0, 'conflict' => 0, 'errors' => 0];

        info(sprintf(
            'Procurando dups apos %s%s (heuristic mode)...',
            $fromDate,
            $dryRun ? ' [DRY-RUN]' : '',
        ));

        $userMorph = (new User)->getMorphClass();

        $usersWithDiscordIdentity = array_flip(ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('model_type', $userMorph)
            ->pluck('model_id')
            ->all());

        $candidates = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('model_type', $userMorph)
            ->whereExists(fn (Builder $q) => $q
                ->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id::text = external_identities.model_id')
                ->whereDate('users.created_at', '>=', $fromDate)
            )
            ->with('user')
            ->orderBy('created_at')
            ->cursor();

        foreach ($candidates as $identity) {
            if ($limit !== null && $stats['merged'] >= $limit) {
                break;
            }

            $newUser = $identity->user;
            if (!$newUser instanceof User) {
                continue;
            }

            $candidateUsernames = $this->extractOrphanUsernameCandidates($identity, $newUser);

            $matches = User::query()
                ->where('id', '!=', $newUser->id)
                ->whereIn('username', $candidateUsernames)
                ->whereDate('created_at', '<', $fromDate)
                ->get()
                ->reject(fn (User $u): bool => isset($usersWithDiscordIdentity[(string) $u->id]));

            if ($matches->isEmpty()) {
                $stats['no_match']++;

                continue;
            }

            if ($matches->count() > 1) {
                $stats['conflict']++;
                Log::warning('merge-duplicate-profiles conflict: multiple orphan candidates', [
                    'new_user_id' => $newUser->id,
                    'username' => $newUser->username,
                    'candidate_ids' => $matches->pluck('id')->all(),
                    'candidate_usernames' => $matches->pluck('username')->all(),
                ]);

                continue;
            }

            $oldUser = $matches->first();

            if ($dryRun) {
                $this->line(sprintf(
                    '  WOULD MERGE  %s (orphan, %s) <- %s (dup, %s)',
                    $oldUser->username,
                    $oldUser->created_at->toDateTimeString(),
                    $newUser->username,
                    $newUser->created_at->toDateTimeString(),
                ));
                $stats['merged']++;

                continue;
            }

            try {
                $merge->handle($oldUser, $newUser, $newUser->username);
                $stats['merged']++;

                if ($stats['merged'] % $batch === 0) {
                    info(sprintf('  ... %d merged', $stats['merged']));
                }
            } catch (Throwable $e) {
                $stats['errors']++;
                Log::error('merge-duplicate-profiles failed', [
                    'new_user_id' => $newUser->id,
                    'old_user_id' => $oldUser->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        table(
            headers: ['Metrica', 'Valor'],
            rows: [
                ['Merged', number_format($stats['merged'])],
                ['Sem match (legitimos novos ou pomelo sem legacy)', number_format($stats['no_match'])],
                ['Conflitos (multiplos candidatos)', number_format($stats['conflict'])],
                ['Erros', number_format($stats['errors'])],
            ],
        );

        if ($stats['conflict'] > 0) {
            warning('Conflitos requerem revisao manual. Detalhes em storage/logs.');
        }

        info($dryRun ? 'Dry-run finalizado.' : 'Merge finalizado.');

        return self::SUCCESS;
    }

    /**
     * Build prioritized list of candidate orphan usernames using identity metadata
     * (more reliable than guessing from current Discord username).
     *
     * @return list<string>
     */
    private function extractOrphanUsernameCandidates(ExternalIdentity $identity, User $newUser): array
    {
        $candidates = [];
        $metadata = $identity->metadata ?? [];

        if (isset($metadata['legacy_username']) && is_string($metadata['legacy_username'])) {
            $candidates[] = $metadata['legacy_username'];
        }

        foreach ($metadata['badges'] ?? [] as $badge) {
            if (!is_array($badge)) {
                continue;
            }

            if (($badge['id'] ?? null) !== 'legacy_username') {
                continue;
            }

            $description = $badge['description'] ?? '';
            if (is_string($description) && preg_match('/Originally known as (.+?)$/', $description, $matches) === 1) {
                $candidates[] = mb_trim($matches[1]);
            }
        }

        $candidates[] = $newUser->username.'#0';
        $candidates[] = $newUser->username;

        return array_values(array_unique($candidates));
    }
}
