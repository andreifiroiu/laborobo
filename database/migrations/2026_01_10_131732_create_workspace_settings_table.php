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
        Schema::create('workspace_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name')->default('My Workspace');
            $table->string('timezone')->default('UTC');
            $table->string('work_week_start')->default('monday');
            $table->string('default_project_status')->default('active');
            $table->string('brand_color')->default('#4f46e5');
            $table->string('logo')->nullable();
            $table->time('working_hours_start')->default('09:00');
            $table->time('working_hours_end')->default('17:00');
            $table->string('date_format')->default('Y-m-d');
            $table->string('currency')->default('USD');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_settings');
    }
};
