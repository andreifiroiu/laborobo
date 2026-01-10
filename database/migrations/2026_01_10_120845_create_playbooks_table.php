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
        Schema::create('playbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['sop', 'checklist', 'template', 'acceptance_criteria']);
            $table->string('name');
            $table->text('description');
            $table->json('content');
            $table->json('tags')->nullable();
            $table->integer('times_applied')->default(0);
            $table->timestamp('last_used')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->string('created_by_name');
            $table->boolean('ai_generated')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'type']);
            $table->index(['team_id', 'created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playbooks');
    }
};
