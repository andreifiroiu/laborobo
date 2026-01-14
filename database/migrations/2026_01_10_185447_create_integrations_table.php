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
        // Master list of available integrations
        Schema::create('available_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category'); // 'communication', 'storage', 'crm', 'analytics'
            $table->text('description');
            $table->string('icon')->nullable(); // URL or icon identifier
            $table->json('features')->nullable(); // Array of feature descriptions
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Team-specific integration connections
        Schema::create('team_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('available_integration_id')->constrained('available_integrations')->cascadeOnDelete();
            $table->boolean('connected')->default(false);
            $table->timestamp('connected_at')->nullable();
            $table->string('connected_by')->nullable(); // User ID who connected it
            $table->json('config')->nullable(); // Integration-specific configuration
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status')->nullable(); // 'success', 'error', 'pending'
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'available_integration_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_integrations');
        Schema::dropIfExists('available_integrations');
    }
};
