<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->decimal('hours', 8, 2);
            $table->date('date');
            $table->string('mode')->default('manual'); // manual, timer, ai_estimation
            $table->text('note')->nullable();
            $table->timestamp('started_at')->nullable(); // For timer mode
            $table->timestamp('stopped_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
