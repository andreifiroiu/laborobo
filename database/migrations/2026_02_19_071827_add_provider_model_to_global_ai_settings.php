<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_ai_settings', function (Blueprint $table) {
            $table->string('default_provider')->default('anthropic')->after('team_id');
            $table->string('default_model')->default('claude-sonnet-4-20250514')->after('default_provider');
        });
    }

    public function down(): void
    {
        Schema::table('global_ai_settings', function (Blueprint $table) {
            $table->dropColumn(['default_provider', 'default_model']);
        });
    }
};
