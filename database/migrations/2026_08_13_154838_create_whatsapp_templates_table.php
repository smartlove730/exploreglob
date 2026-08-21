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
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category'); // MARKETING, UTILITY, AUTHENTICATION
            $table->string('language', 10)->default('en_US');
            $table->string('header_type', 20)->default('NONE'); // TEXT, IMAGE, DOCUMENT, VIDEO
            $table->text('header_content')->nullable();
            $table->text('body');
            $table->text('footer')->nullable();
            $table->json('buttons')->nullable();
            $table->string('status', 20)->default('PENDING'); // APPROVED, PENDING, REJECTED
            $table->timestamps();
        
            $table->unique(['whatsapp_account_id', 'name', 'language'], 'wa_tpl_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
