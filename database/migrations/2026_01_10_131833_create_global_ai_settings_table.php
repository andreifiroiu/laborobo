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
        Schema::create('global_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('total_monthly_budget', 10, 2)->default(2000.00);
            $table->decimal('current_month_spend', 10, 2)->default(0.00);
            $table->decimal('per_project_budget_cap', 10, 2)->default(500.00);

            // Approval requirements
            $table->boolean('approval_client_facing_content')->default(true);
            $table->boolean('approval_financial_data')->default(true);
            $table->boolean('approval_contractual_changes')->default(true);
            $table->boolean('approval_work_order_creation')->default(false);
            $table->boolean('approval_task_assignment')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_ai_settings');
    }
};
