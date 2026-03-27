<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drive_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_key')->nullable();
            $table->text('description')->nullable();
            $table->string('email')->nullable();
            $table->string('redirect_url')->nullable();
            $table->text('oauth_access_token')->nullable();
            $table->text('oauth_refresh_token')->nullable();
            $table->timestamp('oauth_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_api_keys');
    }
};
