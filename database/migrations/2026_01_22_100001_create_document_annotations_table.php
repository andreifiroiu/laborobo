<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->unsignedSmallInteger('page')->nullable(); // Nullable for images
            $table->decimal('x_percent', 6, 2); // 0.00 to 100.00
            $table->decimal('y_percent', 6, 2); // 0.00 to 100.00
            $table->foreignId('communication_thread_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['document_id', 'page']);
            $table->index('communication_thread_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_annotations');
    }
};
