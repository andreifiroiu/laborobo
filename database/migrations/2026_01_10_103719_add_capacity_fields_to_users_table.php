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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->after('language');
            $table->integer('capacity_hours_per_week')->default(40)->after('role');
            $table->integer('current_workload_hours')->default(0)->after('capacity_hours_per_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'capacity_hours_per_week', 'current_workload_hours']);
        });
    }
};
