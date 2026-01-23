<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes author_id nullable to support AI agent drafted messages
     * that don't have a human author.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop the existing foreign key constraint first
            $table->dropForeign(['author_id']);

            // Make the column nullable
            $table->foreignId('author_id')
                ->nullable()
                ->change();

            // Re-add the foreign key with nullable support
            $table->foreign('author_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop the nullable foreign key
            $table->dropForeign(['author_id']);

            // Change back to non-nullable
            $table->foreignId('author_id')
                ->nullable(false)
                ->change();

            // Re-add the original foreign key constraint
            $table->foreign('author_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
