<?php

namespace Database\Seeders;

use App\Actions\CancelRental;
use App\Enums\ChargeType;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Enums\UnitCondition;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalCharge;
use App\Models\RentalItem;
use App\Models\RentalPayment;
use App\Models\Unit;
use App\Models\User;
use App\Support\InvoiceNumber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed demo data: 2 users, 10 customers, 3 categories with daily rates, 110 units (100 HT, 6 mic, 4 TOA)
     * and 20 rentals (5 completed, 5 out now incl. 2 overdue and 1 due soon, 8 upcoming, 2 cancelled), with
     * bills in every payment state: unpaid, DP, paid and overpaid, plus discounts, extra charges and a refund.
     *
     * Users are kept across runs; domain data is wiped and recreated.
     */
    public function run(): void
    {
        $this->call(SuperadminSeeder::class);

        $owner = $this->ensureUser('owner@gmail.com', 'Rina (Owner)', Role::Owner);
        $staff = $this->ensureUser('staff@gmail.com', 'Doni (Staff)', Role::Staff);

        RentalPayment::withTrashed()->forceDelete();
        RentalCharge::withTrashed()->forceDelete();
        RentalItem::withTrashed()->forceDelete();
        Rental::withTrashed()->forceDelete();
        Unit::withTrashed()->forceDelete();
        Category::withTrashed()->forceDelete();
        Customer::withTrashed()->forceDelete();

        $categories = collect(['HT' => 25000, 'Mic' => 50000, 'TOA' => 100000])
            ->map(fn (int $rate, string $name): Category => Category::create(['name' => $name, 'daily_rate' => $rate, 'created_by' => $owner->id]));

        $customers = collect([
            ['Budi Santoso', '0812-1100-2043'],
            ['Sinta Dewi', '0812-2233-8890'],
            ['Andi Pratama', '0821-7788-4510'],
            ['Dewi Lestari', '0857-9032-1174'],
            ['Rina Melati', '0813-4520-9981'],
            ['Hendra Gunawan', '0819-3344-5566'],
            ['Maya Sari', '0812-9988-7766'],
            ['CV Maju Jaya', '021-5567-3300'],
            ['Toko Elektronik Jaya', '021-8890-1247'],
            ['EO Bahagia', '0811-2000-4545'],
        ])->map(fn (array $row): Customer => Customer::create(['name' => $row[0], 'phone' => $row[1], 'created_by' => $staff->id]));

        $units = collect(range(1, 100))
            ->map(fn (int $n): Unit => Unit::create(['category_id' => $categories['HT']->id, 'code' => sprintf('A-%03d', $n), 'created_by' => $owner->id]))
            ->keyBy('code');

        // Extra kinds of equipment, left out of the demo rentals below.
        foreach (['Mic' => ['MIC', 6], 'TOA' => ['TOA', 4]] as $categoryName => [$prefix, $count]) {
            foreach (range(1, $count) as $n) {
                Unit::create(['category_id' => $categories[$categoryName]->id, 'code' => sprintf('%s-%02d', $prefix, $n), 'created_by' => $owner->id]);
            }
        }

        // A few units out of service, none of them used by the demo rentals below.
        foreach (['A-098' => UnitCondition::UnderRepair, 'A-099' => UnitCondition::Damaged, 'A-100' => UnitCondition::Lost, 'MIC-06' => UnitCondition::UnderRepair] as $code => $condition) {
            Unit::query()->where('code', $code)->update(['condition' => $condition->value]);
        }

        // [customer index, unit count or explicit codes, start offset, due offset, returned?, event details, billing]
        // billing: discount [type, value, note]; charges [[type, amount, description]]; payments [[amount or 'rest', days ago, method]],
        // where 'rest' pays whatever is still owed at that point; cancel [reason, fee]; refund [amount, days ago, method].
        $specs = [
            // Completed
            [0, 2, -35, -28, true, [], ['payments' => [['rest', 35, PaymentMethod::Cash]]]], // Lunas
            [1, 1, -30, -25, true, [], ['payments' => [['rest', 25, PaymentMethod::Transfer]]]], // Lunas
            [2, 3, -28, -20, true, ['event_name' => 'Pensi SMA 5', 'event_address' => 'Aula SMA 5, Jakarta'], [
                'discount' => [DiscountType::Percent, 10, 'Acara sekolah'],
                'payments' => [[300000, 30, PaymentMethod::Transfer], ['rest', 20, PaymentMethod::Cash]],
            ]], // Lunas, with a discount
            [3, 1, -22, -18, true, [], ['charges' => [[ChargeType::Damage, 75000, 'Antena A-007 patah']]]], // Belum bayar
            [4, 2, -20, -14, true, ['event_name' => 'Bazaar Ramadhan'], ['payments' => [[200000, 21, PaymentMethod::Qris]]]], // DP
            // Out now
            [5, 2, -12, -4, false, ['event_name' => 'Kondangan Budi', 'event_address' => 'Jl. Melati 12, Jakarta'], [
                'payments' => [[150000, 13, PaymentMethod::Cash]],
            ]], // overdue, DP
            [6, 1, -9, -1, false, [], []], // overdue, Belum bayar
            [7, 4, -2, 5, false, ['event_name' => 'Wedding — Sinta & Andi', 'event_address' => 'Gedung Serbaguna', 'event_social' => '@sinta.wedding'], [
                'discount' => [DiscountType::Amount, 50000, 'Paket wedding'],
                'charges' => [[ChargeType::Delivery, 50000, 'Antar-jemput ke Gedung Serbaguna']],
                'payments' => [[400000, 5, PaymentMethod::Transfer]],
            ]], // DP
            [8, 2, -3, 1, false, [], ['payments' => [['rest', 3, PaymentMethod::Qris]]]], // due soon, Lunas
            [0, 3, -1, 6, false, ['event_name' => 'Company outing', 'event_social' => '@majujaya'], [
                'payments' => [['rest', 2, PaymentMethod::Transfer], [25000, 1, PaymentMethod::Cash]],
            ]], // Lebih bayar
            // Upcoming
            [1, 1, 2, 5, false, [], ['payments' => [[50000, 1, PaymentMethod::Cash]]]], // DP
            [2, 3, 3, 8, false, ['event_name' => 'Ulang tahun anak'], []], // Belum bayar
            [3, 2, 5, 7, false, [], ['cancel' => ['Acara diundur', 0]]], // Dibatalkan, nothing paid
            [9, 5, 6, 10, false, ['event_name' => 'Festival Musik', 'event_address' => 'Lapangan Kota', 'event_social' => '@festmusik'], [
                'payments' => [[500000, 2, PaymentMethod::Transfer]],
            ]], // DP
            [4, 1, 8, 9, false, [], []],
            [5, 2, 10, 14, false, ['event_name' => 'Gathering kantor'], [
                'payments' => [[100000, 4, PaymentMethod::Transfer]],
                'cancel' => ['Kantor batal ikut', 25000],
                'refund' => [75000, 1, PaymentMethod::Transfer],
            ]], // Dibatalkan with a fee; the DP above the fee was refunded
            [6, 1, 12, 13, false, [], []],
            [7, 4, 14, 18, false, ['event_name' => 'Konser amal'], ['discount' => [DiscountType::Percent, 15, 'Acara amal']]],
            [8, 2, 18, 21, false, [], []],
            [9, ['A-001'], 20, 23, false, ['event_name' => 'Reuni akbar'], []], // reuses returned A-001, non-overlapping
        ];

        $cursor = 0;

        foreach ($specs as [$customerIndex, $unitSpec, $startOffset, $dueOffset, $returned, $event, $billing]) {
            $codes = is_array($unitSpec) ? $unitSpec : $units->keys()->slice($cursor, $unitSpec)->all();
            $cursor += is_array($unitSpec) ? 0 : $unitSpec;

            // Booked three days before it starts, or three days ago for upcoming ones.
            $bookedAt = now()->addDays(min($startOffset, 0) - 3);

            // Model events are off while seeding, so the invoice number is set here instead of by Rental::booted().
            $rental = Rental::create([
                'invoice_number' => InvoiceNumber::next($bookedAt),
                'customer_id' => $customers[$customerIndex]->id,
                'start_date' => now()->addDays($startOffset),
                'due_date' => now()->addDays($dueOffset),
                'created_by' => $staff->id,
                ...$event,
                ...(isset($billing['discount']) ? [
                    'discount_type' => $billing['discount'][0],
                    'discount_value' => $billing['discount'][1],
                    'discount_note' => $billing['discount'][2],
                ] : []),
            ]);
            $rental->forceFill(['created_at' => $bookedAt, 'updated_at' => $bookedAt])->saveQuietly();

            foreach ($codes as $code) {
                $rental->items()->create([
                    'unit_id' => $units[$code]->id,
                    'daily_rate' => $categories['HT']->daily_rate, // the demo rentals only use HT units
                    'created_by' => $staff->id,
                    'updated_by' => $returned ? $staff->id : null,
                    'returned_at' => $returned ? now()->addDays($dueOffset) : null,
                ]);
            }

            foreach ($billing['charges'] ?? [] as [$type, $amount, $description]) {
                $rental->charges()->create(['type' => $type, 'amount' => $amount, 'description' => $description, 'created_by' => $staff->id]);
            }

            foreach ($billing['payments'] ?? [] as [$amount, $daysAgo, $method]) {
                $rental->payments()->create([
                    'amount' => $amount === 'rest' ? $rental->fresh()->bill()->balance() : $amount,
                    'paid_at' => now()->subDays($daysAgo),
                    'method' => $method,
                    'created_by' => $staff->id,
                ]);
            }

            $rental->syncTotals();

            if (isset($billing['cancel'])) {
                app(CancelRental::class)->handle($owner, $rental, ['reason' => $billing['cancel'][0], 'fee' => $billing['cancel'][1]]);
            }

            if (isset($billing['refund'])) {
                [$amount, $daysAgo, $method] = $billing['refund'];

                $rental->payments()->create([
                    'amount' => -$amount,
                    'paid_at' => now()->subDays($daysAgo),
                    'method' => $method,
                    'note' => 'Sisa DP dikembalikan',
                    'created_by' => $owner->id,
                ]);
                $rental->syncTotals();
            }
        }

        $this->command?->info(sprintf(
            'Seed complete: %d customers, %d units, %d rentals (5 completed, 5 active, 8 upcoming, 2 cancelled).',
            $customers->count(), $units->count(), count($specs),
        ));
    }

    private function ensureUser(string $email, string $name, Role $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password'],
        );

        $user->syncRoles($role);

        return $user;
    }
}
