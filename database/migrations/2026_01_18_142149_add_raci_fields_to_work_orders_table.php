<?php

declare(strict_types=1);

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
        Schema::table('work_orders', function (Blueprint $table) {
            // Add RACI fields - accountable starts nullable so we can populate existing records
            $table->unsignedBigInteger('accountable_id')->nullable()->after('created_by_id');
            $table->unsignedBigInteger('responsible_id')->nullable()->after('accountable_id');
            $table->unsignedBigInteger('reviewer_id')->nullable()->after('responsible_id');
            $table->json('consulted_ids')->nullable()->after('reviewer_id');
            $table->json('informed_ids')->nullable()->after('consulted_ids');

            $table->index('accountable_id');
            $table->index('responsible_id');
            $table->index('reviewer_id');
        });

        // Set accountable_id to created_by_id for existing records
        DB::statement('UPDATE work_orders SET accountable_id = created_by_id WHERE accountable_id IS NULL');

        // Now make accountable_id required and add foreign key constraints
        Schema::table('work_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('accountable_id')->nullable(false)->change();

            // Add foreign key constraints
            $table->foreign('accountable_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('responsible_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('reviewer_id')
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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['accountable_id']);
            $table->dropForeign(['responsible_id']);
            $table->dropForeign(['reviewer_id']);
            $table->dropIndex(['accountable_id']);
            $table->dropIndex(['responsible_id']);
            $table->dropIndex(['reviewer_id']);
            $table->dropColumn(['accountable_id', 'responsible_id', 'reviewer_id', 'consulted_ids', 'informed_ids']);
        });
    }
};
