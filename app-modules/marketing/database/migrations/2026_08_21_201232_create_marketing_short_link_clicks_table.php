<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_short_link_clicks', static function (Blueprint $table): void {
            // A deliberate divergence from the project UUID convention: this is
            // a high-volume append-only table where a UUID v4 would fragment the
            // B-tree index. See ADR-0003.
            $table->bigIncrements('id');

            $table->foreignUuid('short_link_id')->constrained('marketing_short_links')->cascadeOnDelete();
            $table->timestampTz('clicked_at')->index();

            // Personal data, stored raw and with no retention policy. See ADR-0003.
            $table->string('ip_address', 45);
            $table->text('user_agent');

            $table->text('referer')->nullable();
            $table->char('country_code', 2)->nullable()->comment('Header CF-IPCountry do Cloudflare.');

            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();

            $table->boolean('is_bot')->default(value: false)->index();
            $table->string('bot_name')->nullable();

            // What arrived on the short URL, not the UTM configured on the link.
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();

            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            // No standalone `short_link_id` index: the composite below covers it
            // as a prefix, and one index less makes each write cheaper.
            $table->index(['short_link_id', 'clicked_at'], 'idx_short_link_clicks_timeline');
            $table->index('country_code', 'idx_short_link_clicks_country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_short_link_clicks');
    }
};
