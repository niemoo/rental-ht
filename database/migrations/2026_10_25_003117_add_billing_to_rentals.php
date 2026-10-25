<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money is stored as whole rupiah.
     *
     * categories.daily_rate: the price per unit per day. rental_items.daily_rate: that price copied when
     * the unit was booked, so later price changes don't rewrite old invoices.
     * rentals: invoice number, discount (a rupiah amount or a percent of the subtotal), cancellation, and
     * total_amount / paid_amount kept in sync by Rental::syncTotals() so lists can filter and sort by them.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('daily_rate')->default(0)->after('name');
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->unsignedBigInteger('daily_rate')->default(0)->after('unit_id');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->string('invoice_number', 30)->nullable()->unique()->after('id');
            $table->string('discount_type', 10)->nullable()->after('event_social');
            $table->unsignedBigInteger('discount_value')->default(0)->after('discount_type');
            $table->string('discount_note')->nullable()->after('discount_value');
            $table->bigInteger('total_amount')->default(0)->after('discount_note');
            $table->bigInteger('paid_amount')->default(0)->after('total_amount');
            $table->dateTime('cancelled_at')->nullable()->index()->after('paid_amount');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason')->nullable()->after('cancelled_by');
            $table->unsignedBigInteger('cancellation_fee')->default(0)->after('cancellation_reason');
        });

        // Number existing rentals in the order they were booked, per month.
        $sequences = [];

        DB::table('rentals')->orderBy('created_at')->orderBy('id')->each(function (object $rental) use (&$sequences): void {
            $prefix = 'INV/'.date('Y/m', strtotime($rental->created_at ?? 'now')).'/';
            $sequences[$prefix] = ($sequences[$prefix] ?? 0) + 1;

            DB::table('rentals')->where('id', $rental->id)->update(['invoice_number' => $prefix.str_pad((string) $sequences[$prefix], 4, '0', STR_PAD_LEFT)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropUnique(['invoice_number']);
            $table->dropIndex(['cancelled_at']);
            $table->dropColumn([
                'invoice_number', 'discount_type', 'discount_value', 'discount_note',
                'total_amount', 'paid_amount', 'cancelled_at', 'cancellation_reason', 'cancellation_fee',
            ]);
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropColumn('daily_rate');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('daily_rate');
        });
    }
};
