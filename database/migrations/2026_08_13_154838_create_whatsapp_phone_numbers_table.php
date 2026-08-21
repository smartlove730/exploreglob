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
        Schema::create('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number_id')->unique(); // Meta's Phone Number ID
            $table->string('phone_number'); // Actual number
            $table->string('display_name');
            $table->string('quality_rating', 20)->default('GREEN');
            $table->string('status', 20)->default('PENDING'); // CONNECTED, PENDING, DISCONNECTED
            $table->string('messaging_limit_tier', 20)->default('1K');
            $table->string('business_profile_name')->nullable();
            $table->string('business_profile_category')->nullable();
            $table->text('business_profile_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_phone_numbers');
    }
};
