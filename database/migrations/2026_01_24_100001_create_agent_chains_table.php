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
        Schema::create('agent_chains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('chain_definition');
            $table->boolean('is_template')->default(false);
            $table->boolean('enabled')->default(true);
            $table->foreignId('agent_chain_template_id')->nullable()->constrained('agent_chain_templates')->nullOnDelete();
            $table->timestamps();

            $table->index('team_id');
            $table->index('enabled');
            $table->index('is_template');
            $table->index(['team_id', 'enabled'], 'agent_chains_team_enabled_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_chains');
    }
};
