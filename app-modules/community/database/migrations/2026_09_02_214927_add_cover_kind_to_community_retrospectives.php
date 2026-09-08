<?php

declare(strict_types=1);

use He4rt\Community\Retrospective\Enums\CoverKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_retrospectives', static function (Blueprint $table): void {
            $table->string('cover_kind', 20)
                ->default(CoverKind::Retrospective->value)
                ->comment(CoverKind::stringifyCases())
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('community_retrospectives', static function (Blueprint $table): void {
            $table->dropColumn('cover_kind');
        });
    }
};
