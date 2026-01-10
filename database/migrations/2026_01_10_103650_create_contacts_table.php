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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('party_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('title')->nullable();
            $table->string('role')->nullable();
            $table->string('engagement_type'); // requester, approver, stakeholder, billing
            $table->string('communication_preference')->default('email'); // email, phone, slack
            $table->string('timezone')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'party_id']);
            $table->index(['team_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
