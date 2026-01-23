<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('global_ai_settings', function (Blueprint $table) {
            // PM Copilot auto-suggest setting (opt-in, defaults to false)
            $table->boolean('pm_copilot_auto_suggest')
                ->default(false)
                ->after('require_approval_scope_changes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_ai_settings', function (Blueprint $table) {
            $table->dropColumn('pm_copilot_auto_suggest');
        });
    }
};
