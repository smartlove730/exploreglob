<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'facebook_id')) {
                $table->string('facebook_id')->nullable()->unique()->after('email');
            }

            if (!Schema::hasColumn('users', 'facebook_avatar')) {
                $table->string('facebook_avatar')->nullable()->after('facebook_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'facebook_avatar')) {
                $table->dropColumn('facebook_avatar');
            }

            if (Schema::hasColumn('users', 'facebook_id')) {
                $table->dropUnique('users_facebook_id_unique');
                $table->dropColumn('facebook_id');
            }
        });
    }
};

