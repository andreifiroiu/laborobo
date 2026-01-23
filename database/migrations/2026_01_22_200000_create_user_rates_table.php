<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('internal_rate', 10, 2);
            $table->decimal('billing_rate', 10, 2);
            $table->date('effective_date');
            $table->timestamps();

            $table->index('team_id');
            $table->index('user_id');
            $table->index('effective_date');
            $table->unique(['team_id', 'user_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_rates');
    }
};
