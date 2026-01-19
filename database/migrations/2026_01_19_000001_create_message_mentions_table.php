<?php

declare(strict_types=1);

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
        Schema::create('message_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('mentionable_type');
            $table->unsignedBigInteger('mentionable_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index('message_id');
            $table->index(['mentionable_type', 'mentionable_id'], 'message_mentions_mentionable_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_mentions');
    }
};
