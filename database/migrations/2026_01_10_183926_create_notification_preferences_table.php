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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Email preferences (7 categories)
            $table->boolean('email_project_updates')->default(true);
            $table->boolean('email_task_assignments')->default(true);
            $table->boolean('email_approval_requests')->default(true);
            $table->boolean('email_blockers')->default(true);
            $table->boolean('email_deadlines')->default(true);
            $table->boolean('email_weekly_digest')->default(true);
            $table->boolean('email_agent_activity')->default(true);

            // Push preferences
            $table->boolean('push_project_updates')->default(false);
            $table->boolean('push_task_assignments')->default(true);
            $table->boolean('push_approval_requests')->default(true);
            $table->boolean('push_blockers')->default(true);
            $table->boolean('push_deadlines')->default(true);
            $table->boolean('push_weekly_digest')->default(false);
            $table->boolean('push_agent_activity')->default(false);

            // Slack preferences
            $table->boolean('slack_project_updates')->default(false);
            $table->boolean('slack_task_assignments')->default(false);
            $table->boolean('slack_approval_requests')->default(false);
            $table->boolean('slack_blockers')->default(true);
            $table->boolean('slack_deadlines')->default(false);
            $table->boolean('slack_weekly_digest')->default(false);
            $table->boolean('slack_agent_activity')->default(false);

            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
