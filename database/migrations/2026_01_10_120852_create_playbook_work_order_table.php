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
        Schema::create('playbook_work_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playbook_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attached_by')->constrained('users');
            $table->timestamp('attached_at');
            $table->boolean('ai_suggested')->default(false);

            $table->unique(['playbook_id', 'work_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playbook_work_order');
    }
};
