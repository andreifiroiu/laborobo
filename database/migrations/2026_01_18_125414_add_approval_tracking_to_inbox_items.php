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
        Schema::table('inbox_items', function (Blueprint $table) {
            // Polymorphic relationship for the source item (Task or WorkOrder)
            $table->string('approvable_type')->nullable()->after('related_project_name');
            $table->unsignedBigInteger('approvable_id')->nullable()->after('approvable_type');

            // Related task ID (for Task approvals)
            $table->foreignId('related_task_id')
                ->nullable()
                ->after('related_work_order_title')
                ->constrained('tasks')
                ->nullOnDelete();

            // Reviewer assignment
            $table->foreignId('reviewer_id')
                ->nullable()
                ->after('qa_validation')
                ->constrained('users')
                ->nullOnDelete();

            // Approval status tracking timestamps
            $table->timestamp('approved_at')->nullable()->after('reviewer_id');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');

            // Indexes for efficient querying
            $table->index(['approvable_type', 'approvable_id']);
            $table->index('related_task_id');
            $table->index('reviewer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inbox_items', function (Blueprint $table) {
            $table->dropIndex(['approvable_type', 'approvable_id']);
            $table->dropIndex(['related_task_id']);
            $table->dropIndex(['reviewer_id']);

            $table->dropForeign(['related_task_id']);
            $table->dropForeign(['reviewer_id']);

            $table->dropColumn([
                'approvable_type',
                'approvable_id',
                'related_task_id',
                'reviewer_id',
                'approved_at',
                'rejected_at',
            ]);
        });
    }
};
