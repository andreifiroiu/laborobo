<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('party_contact_id')->nullable()->constrained('parties')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, active, in_review, approved, delivered
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->date('due_date');
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->json('acceptance_criteria')->nullable();
            $table->boolean('sop_attached')->default(false);
            $table->string('sop_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index('project_id');
            $table->index('assigned_to_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
