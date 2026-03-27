<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('automation_configs', function (Blueprint $table) {
            $table->foreignId('drive_api_key_id')
                ->nullable()
                ->after('drive_link')
                ->constrained('drive_api_keys')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('automation_configs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('drive_api_key_id');
        });
    }
};
