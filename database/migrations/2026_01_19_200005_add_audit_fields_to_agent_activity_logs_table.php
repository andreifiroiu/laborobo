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
        Schema::table('agent_activity_logs', function (Blueprint $table) {
            // Tool execution records
            $table->json('tool_calls')->nullable()->after('error');

            // Memory/context audit trail
            $table->json('context_accessed')->nullable()->after('tool_calls');

            // Link to workflow state for workflow tracking
            $table->foreignId('workflow_state_id')
                ->nullable()
                ->after('context_accessed')
                ->constrained('agent_workflow_states')
                ->nullOnDelete();

            // Execution timing
            $table->integer('duration_ms')->nullable()->after('workflow_state_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_activity_logs', function (Blueprint $table) {
            $table->dropForeign(['workflow_state_id']);
            $table->dropColumn([
                'tool_calls',
                'context_accessed',
                'workflow_state_id',
                'duration_ms',
            ]);
        });
    }
};
