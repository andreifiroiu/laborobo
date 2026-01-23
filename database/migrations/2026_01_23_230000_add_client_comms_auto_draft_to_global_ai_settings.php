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
        Schema::table('global_ai_settings', function (Blueprint $table) {
            // Client Communications auto-draft setting (opt-in, defaults to false)
            $table->boolean('client_comms_auto_draft')
                ->default(false)
                ->after('pm_copilot_auto_approval_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_ai_settings', function (Blueprint $table) {
            $table->dropColumn('client_comms_auto_draft');
        });
    }
};
