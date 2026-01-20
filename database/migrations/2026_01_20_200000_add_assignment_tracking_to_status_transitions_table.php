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
        Schema::table('status_transitions', function (Blueprint $table) {
            $table->string('action_type')->default('status_change')->after('transitionable_id');
            $table->foreignId('from_assigned_to_id')->nullable()->after('to_status')->constrained('users')->nullOnDelete();
            $table->foreignId('to_assigned_to_id')->nullable()->after('from_assigned_to_id')->constrained('users')->nullOnDelete();
            $table->foreignId('from_assigned_agent_id')->nullable()->after('to_assigned_to_id')->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('to_assigned_agent_id')->nullable()->after('from_assigned_agent_id')->constrained('ai_agents')->nullOnDelete();

            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_transitions', function (Blueprint $table) {
            $table->dropForeign(['from_assigned_to_id']);
            $table->dropForeign(['to_assigned_to_id']);
            $table->dropForeign(['from_assigned_agent_id']);
            $table->dropForeign(['to_assigned_agent_id']);

            $table->dropIndex(['action_type']);

            $table->dropColumn([
                'action_type',
                'from_assigned_to_id',
                'to_assigned_to_id',
                'from_assigned_agent_id',
                'to_assigned_agent_id',
            ]);
        });
    }
};
