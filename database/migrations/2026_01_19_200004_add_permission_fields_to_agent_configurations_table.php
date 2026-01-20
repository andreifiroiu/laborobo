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
        Schema::table('agent_configurations', function (Blueprint $table) {
            // New permission fields
            $table->boolean('can_modify_deliverables')->default(false)->after('can_send_emails');
            $table->boolean('can_access_financial_data')->default(false)->after('can_modify_deliverables');
            $table->boolean('can_modify_playbooks')->default(false)->after('can_access_financial_data');

            // Daily budget tracking
            $table->decimal('daily_spend', 10, 2)->default(0.00)->after('current_month_spend');

            // Tool-level permission overrides for future use
            $table->json('tool_permissions')->nullable()->after('risk_tolerance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'can_modify_deliverables',
                'can_access_financial_data',
                'can_modify_playbooks',
                'daily_spend',
                'tool_permissions',
            ]);
        });
    }
};
