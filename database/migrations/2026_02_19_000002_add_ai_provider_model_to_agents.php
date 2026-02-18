<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_templates', function (Blueprint $table) {
            $table->string('default_ai_provider')->nullable()->after('is_active');
            $table->string('default_ai_model')->nullable()->after('default_ai_provider');
        });

        Schema::table('agent_configurations', function (Blueprint $table) {
            $table->string('ai_provider')->nullable()->after('ai_agent_id');
            $table->string('ai_model')->nullable()->after('ai_provider');
        });
    }

    public function down(): void
    {
        Schema::table('agent_templates', function (Blueprint $table) {
            $table->dropColumn(['default_ai_provider', 'default_ai_model']);
        });

        Schema::table('agent_configurations', function (Blueprint $table) {
            $table->dropColumn(['ai_provider', 'ai_model']);
        });
    }
};
