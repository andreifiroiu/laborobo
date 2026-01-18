<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('created_by_id')
                ->nullable()
                ->after('assigned_to_id')
                ->constrained('users')
                ->onDelete('set null');

            $table->foreignId('reviewer_id')
                ->nullable()
                ->after('created_by_id')
                ->constrained('users')
                ->onDelete('set null');

            $table->index('created_by_id');
            $table->index('reviewer_id');
        });

        // For existing records, set created_by_id to assigned_to_id as a sensible default
        DB::table('tasks')
            ->whereNull('created_by_id')
            ->whereNotNull('assigned_to_id')
            ->update(['created_by_id' => DB::raw('assigned_to_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropForeign(['reviewer_id']);
            $table->dropIndex(['created_by_id']);
            $table->dropIndex(['reviewer_id']);
            $table->dropColumn(['created_by_id', 'reviewer_id']);
        });
    }
};
