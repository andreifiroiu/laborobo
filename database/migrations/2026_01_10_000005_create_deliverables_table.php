<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('other'); // document, design, report, code, other
            $table->string('status')->default('draft'); // draft, in_review, approved, delivered
            $table->string('version')->default('1.0');
            $table->date('created_date');
            $table->date('delivered_date')->nullable();
            $table->string('file_url')->nullable();
            $table->json('acceptance_criteria')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('work_order_id');
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverables');
    }
};
