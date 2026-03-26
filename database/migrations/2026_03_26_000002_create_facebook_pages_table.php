<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facebook_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facebook_account_id')->constrained()->cascadeOnDelete();
            $table->string('page_id')->unique();
            $table->string('page_name');
            $table->text('page_access_token');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['facebook_account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_pages');
    }
};
