<?php

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
        Schema::create('playbook_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playbook_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->json('content_snapshot');
            $table->foreignId('modified_by')->constrained('users');
            $table->string('modified_by_name');
            $table->text('change_description');
            $table->timestamp('created_at');

            $table->unique(['playbook_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playbook_versions');
    }
};
