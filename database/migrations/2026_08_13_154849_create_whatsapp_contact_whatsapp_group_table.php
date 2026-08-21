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
        Schema::create('whatsapp_contact_whatsapp_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_group_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        
            $table->unique(['whatsapp_contact_id', 'whatsapp_group_id'], 'contact_group_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contact_whatsapp_group');
    }
};
