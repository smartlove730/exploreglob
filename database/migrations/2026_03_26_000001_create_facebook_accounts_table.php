<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facebook_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('long_lived_user_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_accounts');
    }
};
