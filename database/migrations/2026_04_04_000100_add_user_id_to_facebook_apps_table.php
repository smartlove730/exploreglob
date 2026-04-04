<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facebook_apps', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $adminId = DB::table('users')
            ->where('is_admin', true)
            ->orWhere('role', 'admin')
            ->orderBy('id')
            ->value('id');

        if ($adminId) {
            DB::table('facebook_apps')->whereNull('user_id')->update(['user_id' => $adminId]);
        }
    }

    public function down(): void
    {
        Schema::table('facebook_apps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
