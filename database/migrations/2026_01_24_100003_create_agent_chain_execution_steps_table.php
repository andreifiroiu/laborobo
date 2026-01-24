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
        Schema::create('agent_chain_execution_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_chain_execution_id')->constrained('agent_chain_executions')->cascadeOnDelete();
            $table->foreignId('agent_workflow_state_id')->nullable()->constrained('agent_workflow_states')->nullOnDelete();
            $table->unsignedInteger('step_index');
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('output_data')->nullable();
            $table->timestamps();

            $table->index('agent_chain_execution_id');
            $table->index('agent_workflow_state_id');
            $table->index(['agent_chain_execution_id', 'step_index'], 'chain_steps_execution_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_chain_execution_steps');
    }
};
