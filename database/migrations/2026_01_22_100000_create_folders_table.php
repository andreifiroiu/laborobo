<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('folders')->onDelete('cascade');
            $table->string('name');
            $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('team_id');
            $table->index('project_id');
            $table->index('parent_id');
            $table->index(['team_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
