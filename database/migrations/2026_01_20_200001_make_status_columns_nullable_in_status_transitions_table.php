<?php

declare(strict_types=1);

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
        Schema::table('status_transitions', function (Blueprint $table) {
            $table->string('from_status')->nullable()->change();
            $table->string('to_status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_transitions', function (Blueprint $table) {
            $table->string('from_status')->nullable(false)->change();
            $table->string('to_status')->nullable(false)->change();
        });
    }
};
