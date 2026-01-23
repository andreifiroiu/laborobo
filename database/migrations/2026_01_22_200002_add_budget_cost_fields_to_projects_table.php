<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('budget_type')->nullable()->after('budget_hours');
            $table->decimal('budget_cost', 12, 2)->nullable()->after('budget_type');
            $table->decimal('actual_cost', 12, 2)->nullable()->default(0)->after('actual_hours');
            $table->decimal('actual_revenue', 12, 2)->nullable()->default(0)->after('actual_cost');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['budget_type', 'budget_cost', 'actual_cost', 'actual_revenue']);
        });
    }
};
