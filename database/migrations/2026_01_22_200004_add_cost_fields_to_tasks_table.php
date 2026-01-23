<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('actual_cost', 12, 2)->nullable()->default(0)->after('actual_hours');
            $table->decimal('actual_revenue', 12, 2)->nullable()->default(0)->after('actual_cost');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['actual_cost', 'actual_revenue']);
        });
    }
};
