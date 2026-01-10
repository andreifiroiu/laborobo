<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('communication_thread_id')->constrained()->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('author_type')->default('human'); // human, ai_agent
            $table->text('content');
            $table->string('type')->default('note'); // note, suggestion, decision, question
            $table->timestamps();

            $table->index('communication_thread_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
