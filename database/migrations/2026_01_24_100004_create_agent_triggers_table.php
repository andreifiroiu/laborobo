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
        Schema::create('agent_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('entity_type');
            $table->string('status_from')->nullable();
            $table->string('status_to')->nullable();
            $table->foreignId('agent_chain_id')->constrained('agent_chains')->cascadeOnDelete();
            $table->json('trigger_conditions')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index('team_id');
            $table->index('entity_type');
            $table->index('status_from');
            $table->index('status_to');
            $table->index('enabled');
            $table->index(['team_id', 'entity_type', 'enabled'], 'triggers_team_entity_enabled_index');
            $table->index(['entity_type', 'status_from', 'status_to'], 'triggers_status_transition_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_triggers');
    }
};
