<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable(); // Nullable for permanent links
            $table->string('password_hash')->nullable();
            $table->boolean('allow_download')->default(false);
            $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_share_links');
    }
};
