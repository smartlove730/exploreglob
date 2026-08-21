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
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_account_id')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->string('api_version', 10)->default('v20.0');
            
            // Auto-reply Settings
            $table->boolean('auto_reply_enabled')->default(false);
            $table->text('welcome_message')->nullable();
            $table->text('away_message')->nullable();
            $table->json('business_hours')->nullable();
            $table->unsignedInteger('auto_reply_delay_seconds')->default(0);
            
            // Notifications
            $table->boolean('notify_email_enabled')->default(false);
            $table->string('notify_email_address')->nullable();
            $table->string('slack_webhook_url')->nullable();
            $table->json('notify_events')->nullable();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
