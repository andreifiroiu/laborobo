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
        Schema::table('parties', function (Blueprint $table) {
            $table->string('email')->nullable()->after('contact_email');
            $table->string('phone')->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            $table->text('address')->nullable()->after('website');
            $table->text('notes')->nullable()->after('address');
            $table->json('tags')->nullable()->after('notes');
            $table->string('status')->default('active')->after('tags');
            $table->foreignId('primary_contact_id')->nullable()->after('status');
            $table->timestamp('last_activity')->nullable()->after('primary_contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'phone',
                'website',
                'address',
                'notes',
                'tags',
                'status',
                'primary_contact_id',
                'last_activity',
            ]);
        });
    }
};
