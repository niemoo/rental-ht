<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * units.condition: the unit's current physical condition (App\Enums\UnitCondition); existing units start as good.
     * rental_items.return_condition: the condition recorded when that unit came back, so damage or loss
     * can be traced to the rental it happened on.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('condition', 20)->default('good')->after('code')->index();
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->string('return_condition', 20)->nullable()->after('returned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropColumn('return_condition');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex(['condition']);
            $table->dropColumn('condition');
        });
    }
};
