<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('work_order_list_id')
                ->nullable()
                ->after('project_id')
                ->constrained('work_order_lists')
                ->onDelete('set null');
            $table->unsignedInteger('position_in_list')->default(0)->after('work_order_list_id');

            $table->index(['work_order_list_id', 'position_in_list']);
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['work_order_list_id']);
            $table->dropIndex(['work_order_list_id', 'position_in_list']);
            $table->dropColumn(['work_order_list_id', 'position_in_list']);
        });
    }
};
