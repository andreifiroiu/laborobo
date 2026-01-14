<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('blocker_reason')->nullable()->after('is_blocked');
            $table->text('blocker_details')->nullable()->after('blocker_reason');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['blocker_reason', 'blocker_details']);
        });
    }
};
