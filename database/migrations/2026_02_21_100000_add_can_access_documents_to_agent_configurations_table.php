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
        Schema::table('agent_configurations', function (Blueprint $table) {
            $table->boolean('can_access_documents')->default(false)->after('can_modify_playbooks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_configurations', function (Blueprint $table) {
            $table->dropColumn('can_access_documents');
        });
    }
};
