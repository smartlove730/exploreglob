<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('razorpay_plan_id')->nullable()->after('slug')->unique();
            $table->index(['is_active', 'razorpay_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'razorpay_plan_id']);
            $table->dropUnique(['razorpay_plan_id']);
            $table->dropColumn('razorpay_plan_id');
        });
    }
};
