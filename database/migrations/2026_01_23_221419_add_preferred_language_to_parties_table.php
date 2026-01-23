<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->string('preferred_language', 10)->nullable()->default('en')->after('tags');

            $table->index('preferred_language');
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex(['preferred_language']);
            $table->dropColumn('preferred_language');
        });
    }
};
