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
        Schema::create('billing_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();

            // Plan information
            $table->string('plan_name'); // 'Free', 'Pro', 'Enterprise'
            $table->decimal('plan_price', 10, 2);
            $table->string('billing_cycle'); // 'monthly', 'annually'
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->date('next_billing_date');

            // Usage metrics
            $table->integer('users_included');
            $table->integer('users_current');
            $table->integer('projects_included');
            $table->integer('projects_current');
            $table->decimal('storage_gb_included', 10, 2);
            $table->decimal('storage_gb_current', 10, 2);
            $table->integer('ai_requests_included');
            $table->integer('ai_requests_current');

            // Payment information
            $table->string('payment_method')->nullable(); // 'card', 'invoice'
            $table->string('card_brand')->nullable();
            $table->string('card_last4')->nullable();
            $table->date('card_expiry')->nullable();

            // Status
            $table->string('status')->default('active'); // 'active', 'past_due', 'canceled', 'trial'
            $table->date('trial_ends_at')->nullable();

            $table->timestamps();

            $table->unique('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_info');
    }
};
