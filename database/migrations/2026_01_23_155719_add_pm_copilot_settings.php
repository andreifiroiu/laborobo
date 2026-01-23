<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds PM Copilot configuration settings:
     * - pm_copilot_mode on work_orders: Controls workflow behavior (staged/full)
     * - pm_copilot_auto_approval_threshold on global_ai_settings: Confidence threshold for auto-approval
     */
    public function up(): void
    {
        // Add pm_copilot_mode to work_orders table
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('pm_copilot_mode', 20)
                ->nullable()
                ->after('sop_name')
                ->comment('PM Copilot workflow mode: full or staged');
        });

        // Add pm_copilot_auto_approval_threshold to global_ai_settings table
        Schema::table('global_ai_settings', function (Blueprint $table) {
            $table->decimal('pm_copilot_auto_approval_threshold', 3, 2)
                ->default(0.80)
                ->after('pm_copilot_auto_suggest')
                ->comment('Confidence threshold (0-1) for auto-approving low-risk PM Copilot suggestions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('pm_copilot_mode');
        });

        Schema::table('global_ai_settings', function (Blueprint $table) {
            $table->dropColumn('pm_copilot_auto_approval_threshold');
        });
    }
};
