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
        Schema::create('agent_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_agent_id')->constrained();
            $table->string('run_type');
            $table->text('input');
            $table->text('output')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost', 10, 4)->default(0.0000);
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'auto_approved', 'failed'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'ai_agent_id']);
            $table->index(['team_id', 'approval_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_activity_logs');
    }
};
