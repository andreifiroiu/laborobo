<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_user_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('internal_rate', 10, 2);
            $table->decimal('billing_rate', 10, 2);
            $table->date('effective_date');
            $table->timestamps();

            $table->index('project_id');
            $table->index('user_id');
            $table->index('effective_date');
            $table->unique(['project_id', 'user_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_user_rates');
    }
};
