<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Enums\AttributionMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O metadata carregava quatro chaves: três copiavam a origem (repo, lake_ref, url)
 * e uma era fato próprio da interação (matched_by). As três cópias saem — a origem
 * responde por elas pelo contrato ContributionDetail — e a que sobra vira coluna,
 * consultável e indexável, em vez de um jsonb com um valor dentro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interactions', static function (Blueprint $table): void {
            $table->string('attributed_by')
                ->nullable()
                ->after('type')
                ->comment(AttributionMethod::stringifyCases());
        });

        $this->backfillAttribution();

        Schema::table('interactions', static function (Blueprint $table): void {
            $table->string('attributed_by')->nullable(value: false)->change();
            $table->index(['attributed_by', 'occurred_at'], 'idx_interactions_attribution');
            $table->dropColumn('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('interactions', static function (Blueprint $table): void {
            $table->jsonb('metadata')->nullable()->after('source_id');
            $table->dropIndex('idx_interactions_attribution');
        });

        DB::statement(<<<'SQL'
            UPDATE interactions AS i
               SET metadata = jsonb_build_object(
                       'matched_by', CASE i.attributed_by
                                          WHEN 'external_id' THEN 'actor_id'
                                          ELSE 'login'
                                     END,
                       'repo', g.repo,
                       'lake_ref', g.external_ref,
                       'url', g.metadata::jsonb->>'url'
                   )
              FROM github_contributions AS g
             WHERE i.source_type = 'github_contribution'
               AND i.source_id = g.id::text
        SQL);

        Schema::table('interactions', static function (Blueprint $table): void {
            $table->dropColumn('attributed_by');
        });
    }

    /**
     * As linhas de conteúdo nunca passaram por casamento: o artigo chega pela
     * relação de autoria, então a origem já sabia o dono.
     */
    private function backfillAttribution(): void
    {
        DB::statement(<<<'SQL'
            UPDATE interactions
               SET attributed_by = CASE metadata::jsonb->>'matched_by'
                                        WHEN 'actor_id' THEN 'external_id'
                                        WHEN 'login' THEN 'handle'
                                        ELSE 'owned'
                                   END
        SQL);
    }
};
