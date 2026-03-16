<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete()
                ->after('country_id');
        });

        $travelSubcategoryNames = [
            'Travel Guides',
            'Destinations',
            'Things To Do',
            'Travel Itineraries',
            'Budget Travel',
            'Travel Tips',
            'Adventure Travel',
            'Food & Culture',
            'Solo Travel',
            'Luxury Travel',
        ];

        $travelParents = DB::table('categories')
            ->where('name', 'Travel')
            ->get(['id', 'country_id']);

        foreach ($travelParents as $parent) {
            DB::table('categories')
                ->where('country_id', $parent->country_id)
                ->whereIn('name', $travelSubcategoryNames)
                ->update(['parent_id' => $parent->id]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
