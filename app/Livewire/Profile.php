<?php

namespace App\Livewire;

use App\Enums\Permission;
use App\Models\Rental;
use App\Models\RentalPayment;
use App\Models\User;
use App\Support\RentalBill;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The signed-in user's own profile (Velzon's profile page): who they are, what their role
 * lets them do, and the rentals and payments they recorded lately.
 */
#[Title('Profil saya')]
class Profile extends Component
{
    /** How many entries the Aktivitas tab lists. */
    private const ACTIVITY_LIMIT = 15;

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('livewire.profile', [
            'user' => $user,
            'activities' => $this->recentActivities($user),
            'permissions' => collect(Permission::grouped())->map(
                fn (array $group): array => array_map(fn (Permission $permission): array => [
                    'label' => $permission->label(),
                    'granted' => $user->can($permission),
                ], $group),
            ),
        ]);
    }

    /**
     * Rentals the user created and payments they recorded, newest first.
     *
     * @return Collection<int, array{icon: string, tone: string, title: string, detail: string, at: CarbonInterface, rental: Rental}>
     */
    private function recentActivities(User $user): Collection
    {
        $rentals = Rental::query()
            ->where('created_by', $user->id)
            ->with('customer')
            ->latest()
            ->take(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Rental $rental): array => [
                'icon' => 'calendar-days',
                'tone' => 'primary',
                'title' => 'Membuat penyewaan '.$rental->invoice_number,
                'detail' => collect([$rental->customer?->name, $rental->event_name])->filter()->implode(' · '),
                'at' => $rental->created_at,
                'rental' => $rental,
            ]);

        $payments = RentalPayment::query()
            ->where('created_by', $user->id)
            ->whereHas('rental')
            ->with('rental.customer')
            ->latest()
            ->take(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (RentalPayment $payment): array => [
                'icon' => 'wallet',
                'tone' => $payment->isRefund() ? 'warning' : 'success',
                'title' => ($payment->isRefund() ? 'Mencatat pengembalian dana ' : 'Mencatat pembayaran ').RentalBill::rupiah(abs($payment->amount)),
                'detail' => collect([$payment->rental->invoice_number, $payment->rental->customer?->name, $payment->method->label()])->filter()->implode(' · '),
                'at' => $payment->created_at,
                'rental' => $payment->rental,
            ]);

        return $rentals->concat($payments)
            ->sortByDesc(fn (array $activity): int => $activity['at']->getTimestamp())
            ->take(self::ACTIVITY_LIMIT)
            ->values();
    }
}
