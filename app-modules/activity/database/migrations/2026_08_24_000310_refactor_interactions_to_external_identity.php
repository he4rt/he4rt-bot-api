<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interactions', static function (Blueprint $table): void {
            $table->uuid('external_identity_id')->nullable()->after('id');
            $table->uuid('user_id')->nullable()->after('external_identity_id');
            $table->timestampTz('hidden_at')->nullable()->after('occurred_at');
            $table->uuid('hidden_by')->nullable()->after('hidden_at');
        });

        $this->resolveOwnersFromCharacters();
        $this->guardAgainstUnresolvedRows();

        Schema::table('interactions', static function (Blueprint $table): void {
            // Índice único simples, não constraint — dropUnique não o alcança.
            $table->dropIndex('uniq_interactions_provider_ref');
            $table->dropIndex('idx_interactions_character_type');
            $table->dropIndex('idx_interactions_status_tier');
        });

        Schema::table('interactions', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('character_id');
            $table->dropColumn([
                'provider',
                'value_tier',
                'coins_min',
                'coins_max',
                'coins_awarded',
                'xp_awarded',
                'status',
                'reviewed_at',
            ]);
        });

        Schema::table('interactions', static function (Blueprint $table): void {
            $table->uuid('external_identity_id')->nullable(value: false)->change();
            $table->uuid('user_id')->nullable(value: false)->change();

            $table->foreign('external_identity_id')->references('id')->on('external_identities');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('hidden_by')->references('id')->on('users');

            $table->unique('external_ref', 'uniq_interactions_external_ref');
            $table->index(['user_id', 'occurred_at'], 'idx_interactions_user_occurred');
            $table->index('external_identity_id', 'idx_interactions_identity');
        });
    }

    public function down(): void
    {
        Schema::table('interactions', static function (Blueprint $table): void {
            $table->uuid('character_id')->nullable()->after('id');
            $table->string('provider')->nullable()->after('type');
            $table->string('value_tier')->nullable()->after('provider');
            $table->integer('coins_min')->default(0);
            $table->integer('coins_max')->default(0);
            $table->integer('coins_awarded')->nullable();
            $table->integer('xp_awarded')->nullable();
            $table->string('status')->default('pending');
            $table->timestampTz('reviewed_at')->nullable();
        });

        DB::statement(<<<'SQL'
            UPDATE interactions AS i
               SET character_id = c.id,
                   provider = e.provider,
                   value_tier = 'low'
              FROM external_identities AS e
              JOIN characters AS c ON c.user_id::text = e.model_id
             WHERE e.id = i.external_identity_id
        SQL);

        Schema::table('interactions', static function (Blueprint $table): void {
            $table->dropUnique('uniq_interactions_external_ref');
            $table->dropIndex('idx_interactions_user_occurred');
            $table->dropIndex('idx_interactions_identity');

            $table->dropConstrainedForeignId('external_identity_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('hidden_by');
            $table->dropColumn('hidden_at');
        });

        Schema::table('interactions', static function (Blueprint $table): void {
            $table->uuid('character_id')->nullable(value: false)->change();
            $table->string('provider')->nullable(value: false)->change();
            $table->string('value_tier')->nullable(value: false)->change();

            $table->foreign('character_id')->references('id')->on('characters');
            $table->unique(['provider', 'external_ref'], 'uniq_interactions_provider_ref');
            $table->index(['character_id', 'type', 'created_at'], 'idx_interactions_character_type');
            $table->index(['status', 'value_tier'], 'idx_interactions_status_tier');
        });
    }

    /**
     * Cada interação existente aponta para um Character. O dono passa a ser a
     * identidade externa daquele usuário no provider que a própria linha registra.
     */
    private function resolveOwnersFromCharacters(): void
    {
        DB::statement(<<<'SQL'
            UPDATE interactions AS i
               SET external_identity_id = resolved.identity_id,
                   user_id = resolved.user_id
              FROM (
                    SELECT DISTINCT ON (i2.id)
                           i2.id AS interaction_id,
                           e.id AS identity_id,
                           c.user_id AS user_id
                      FROM interactions AS i2
                      JOIN characters AS c ON c.id = i2.character_id
                      JOIN external_identities AS e
                        ON e.model_id = c.user_id::text
                       AND e.provider = i2.provider
                       AND e.deleted_at IS NULL
                       AND e.disconnected_at IS NULL
                     ORDER BY i2.id, e.connected_at DESC NULLS LAST
                   ) AS resolved
             WHERE i.id = resolved.interaction_id
        SQL);
    }

    private function guardAgainstUnresolvedRows(): void
    {
        $unresolved = DB::table('interactions')->whereNull('external_identity_id')->count();

        throw_if(
            $unresolved > 0,
            RuntimeException::class,
            "Não foi possível resolver a identidade externa de {$unresolved} interação(ões). "
            .'Resolva ou remova essas linhas antes de aplicar a migration.',
        );
    }
};
