<?php

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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamp('timestamp')->useCurrent();

            // Actor info
            $table->string('actor')->comment('user:123, agent:456, system');
            $table->string('actor_name');
            $table->enum('actor_type', ['user', 'agent', 'system']);

            // Action
            $table->string('action');

            // Target (optional)
            $table->string('target')->nullable();
            $table->string('target_id')->nullable();

            // Details
            $table->text('details');
            $table->string('ip_address')->nullable();

            $table->timestamps();
            $table->index(['team_id', 'timestamp']);
            $table->index(['team_id', 'action']);
            $table->index(['team_id', 'actor_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
