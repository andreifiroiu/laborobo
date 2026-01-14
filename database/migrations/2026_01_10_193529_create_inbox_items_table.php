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
        Schema::create('inbox_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();

            // Item metadata
            $table->enum('type', ['agent_draft', 'approval', 'flag', 'mention']);
            $table->string('title');
            $table->text('content_preview');
            $table->longText('full_content');

            // Source information
            $table->string('source_id');  // agent-001, tm-002, etc.
            $table->string('source_name');
            $table->enum('source_type', ['human', 'ai_agent']);

            // Related entities (nullable - not all items link to work)
            $table->foreignId('related_work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->string('related_work_order_title')->nullable();
            $table->foreignId('related_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('related_project_name')->nullable();

            // Priority and quality
            $table->enum('urgency', ['urgent', 'high', 'normal'])->default('normal');
            $table->enum('ai_confidence', ['high', 'medium', 'low'])->nullable();
            $table->enum('qa_validation', ['passed', 'failed'])->nullable();

            // Timestamps
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes(); // For archiving

            // Indexes for performance
            $table->index(['team_id', 'type']);
            $table->index(['team_id', 'urgency']);
            $table->index(['team_id', 'created_at']);
            $table->index('source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_items');
    }
};
