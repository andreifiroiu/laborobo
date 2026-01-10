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
        Schema::create('agent_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_agent_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->integer('daily_run_limit')->default(50);
            $table->integer('weekly_run_limit')->default(300);
            $table->decimal('monthly_budget_cap', 10, 2)->default(100.00);
            $table->decimal('current_month_spend', 10, 2)->default(0.00);

            // Permissions
            $table->boolean('can_create_work_orders')->default(false);
            $table->boolean('can_modify_tasks')->default(false);
            $table->boolean('can_access_client_data')->default(true);
            $table->boolean('can_send_emails')->default(false);
            $table->boolean('requires_approval')->default(true);

            // Behavior settings
            $table->enum('verbosity_level', ['concise', 'balanced', 'detailed'])->default('balanced');
            $table->enum('creativity_level', ['low', 'balanced', 'high'])->default('balanced');
            $table->enum('risk_tolerance', ['low', 'medium', 'high'])->default('low');

            $table->timestamps();
            $table->unique(['team_id', 'ai_agent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_configurations');
    }
};
