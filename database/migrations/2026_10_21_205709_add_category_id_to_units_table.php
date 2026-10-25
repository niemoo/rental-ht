<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every unit belongs to a category. Units that existed before categories were all HTs,
     * so they are moved into an "HT" category before the column becomes required.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        if (DB::table('units')->exists()) {
            $categoryId = DB::table('categories')->insertGetId(['name' => 'HT', 'created_at' => now(), 'updated_at' => now()]);

            DB::table('units')->update(['category_id' => $categoryId]);
        }

        Schema::table('units', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
