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
            // Retention configuration
            $table->integer('retention_days')->default(90)->after('approval_task_assignment');

            // System-level approval requirements for high-risk actions
            $table->boolean('require_approval_external_sends')->default(true)->after('retention_days');
            $table->boolean('require_approval_financial')->default(true)->after('require_approval_external_sends');
            $table->boolean('require_approval_contracts')->default(true)->after('require_approval_financial');
            $table->boolean('require_approval_scope_changes')->default(true)->after('require_approval_contracts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_ai_settings', function (Blueprint $table) {
            $table->dropColumn([
                'retention_days',
                'require_approval_external_sends',
                'require_approval_financial',
                'require_approval_contracts',
                'require_approval_scope_changes',
            ]);
        });
    }
};
