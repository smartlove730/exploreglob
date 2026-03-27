<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drive_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('folder_url');
            $table->string('folder_id')->nullable();
            $table->foreignId('drive_api_key_id')->nullable()->constrained('drive_api_keys')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_folders');
    }
};
