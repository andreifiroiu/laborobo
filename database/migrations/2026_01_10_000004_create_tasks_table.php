<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo'); // todo, in_progress, done
            $table->date('due_date');
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->json('checklist_items')->nullable(); // [{id, text, completed}]
            $table->json('dependencies')->nullable(); // [task_ids]
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index('work_order_id');
            $table->index('assigned_to_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
