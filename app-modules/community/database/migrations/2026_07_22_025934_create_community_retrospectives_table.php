<?php

declare(strict_types=1);

use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_retrospectives', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->timestampTz('since');
            $table->timestampTz('until');
            $table->string('status', 20)->default(RetrospectiveStatus::Draft->value)->comment(RetrospectiveStatus::stringifyCases());
            $table->string('cover_title')->nullable();
            $table->text('cover_intro')->nullable();
            $table->text('closing_text')->nullable();
            $table->boolean('hide_bots')->default(value: true);
            // Curadoria de apresentação (ordem, on/off, exclusions). Cast AsDeckConfig
            // normaliza null para um DeckConfig vazio na leitura.
            $table->jsonb('deck_config')->nullable();
            // SourceResult[] congelados no publish; null enquanto rascunho.
            $table->jsonb('snapshot')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();

            // Página pública busca a edição publicada mais recente.
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_retrospectives');
    }
};
