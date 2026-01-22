<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_share_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_share_link_id')->constrained()->onDelete('cascade');
            $table->timestamp('accessed_at');
            $table->string('ip_address', 45)->nullable(); // IPv6 can be up to 45 chars
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('document_share_link_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_share_accesses');
    }
};
