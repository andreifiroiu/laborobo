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
        Schema::create('agent_chain_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_chain_id')->constrained('agent_chains')->cascadeOnDelete();
            $table->unsignedInteger('current_step_index')->default(0);
            $table->string('execution_status')->default('pending');
            $table->json('chain_context')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();

            // Morph relationship to triggering entity (work_order, task, deliverable)
            $table->string('triggerable_type')->nullable();
            $table->unsignedBigInteger('triggerable_id')->nullable();

            $table->timestamps();

            $table->index('team_id');
            $table->index('agent_chain_id');
            $table->index('execution_status');
            $table->index('completed_at');
            $table->index(['team_id', 'execution_status'], 'chain_executions_team_status_index');
            $table->index(['triggerable_type', 'triggerable_id'], 'chain_executions_triggerable_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_chain_executions');
    }
};
