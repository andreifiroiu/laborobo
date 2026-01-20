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
        Schema::create('agent_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->enum('scope', ['project', 'client', 'org']);
            $table->string('scope_type');
            $table->unsignedBigInteger('scope_id');
            $table->string('key');
            $table->json('value');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite index for efficient lookups by team, scope, and key
            $table->index(['team_id', 'scope', 'scope_type', 'scope_id', 'key'], 'agent_memories_scope_lookup');
            $table->index(['team_id', 'ai_agent_id']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_memories');
    }
};
