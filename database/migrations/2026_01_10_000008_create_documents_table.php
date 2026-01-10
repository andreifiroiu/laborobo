<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by_id')->constrained('users')->onDelete('cascade');
            $table->morphs('documentable'); // documentable_type, documentable_id - morphs already creates index
            $table->string('name');
            $table->string('type')->default('artifact'); // reference, artifact, evidence, template
            $table->string('file_url');
            $table->string('file_size')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
