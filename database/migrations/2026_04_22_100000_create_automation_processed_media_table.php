<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_processed_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('automation_configs')->cascadeOnDelete();
            $table->string('file_id');
            $table->string('folder_id')->nullable();
            $table->enum('status', ['pending', 'posted', 'skipped', 'failed'])->default('pending');
            $table->timestamps();

            $table->unique('file_id');
            $table->index('automation_id');
            $table->index('folder_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_processed_media');
    }
};
