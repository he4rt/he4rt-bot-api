<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->timestampTz('username_manually_set_at')->nullable()->after('username');
            $table->timestampTz('username_updated_at')->nullable()->after('username_manually_set_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropColumn(['username_manually_set_at', 'username_updated_at']);
        });
    }
};
