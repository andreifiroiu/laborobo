<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('draft_status')->nullable()->after('type');
            $table->json('draft_metadata')->nullable()->after('draft_status');
            $table->timestamp('approved_at')->nullable()->after('draft_metadata');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            $table->index('draft_status');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['draft_status']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'draft_status',
                'draft_metadata',
                'approved_at',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
