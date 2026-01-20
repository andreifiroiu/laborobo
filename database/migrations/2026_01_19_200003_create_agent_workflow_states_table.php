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
        Schema::create('agent_workflow_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_agent_id')->constrained('ai_agents')->cascadeOnDelete();
            $table->string('workflow_class');
            $table->string('current_node');
            $table->json('state_data');
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('pause_reason')->nullable();
            $table->boolean('approval_required')->default(false);

            // Morph relationship to InboxItem for approval workflow
            $table->string('approvable_type')->nullable();
            $table->unsignedBigInteger('approvable_id')->nullable();

            $table->timestamps();

            $table->index('team_id');
            $table->index('ai_agent_id');
            $table->index(['team_id', 'ai_agent_id', 'completed_at'], 'workflow_states_status_lookup');
            $table->index(['approvable_type', 'approvable_id'], 'workflow_states_approvable_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_workflow_states');
    }
};
