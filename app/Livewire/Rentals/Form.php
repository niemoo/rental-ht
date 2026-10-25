<?php

namespace App\Livewire\Rentals;

use App\Actions\CreateRental;
use App\Actions\UpdateRental;
use App\Enums\DiscountType;
use App\Enums\Permission;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\Unit;
use App\Support\RentalBill;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Book a rental (rentals.create) or change one that isn't completed yet (rentals.edit).
 * When editing, units already returned stay on the rental and can't be taken off.
 */
class Form extends Component
{
    public ?Rental $rental = null;

    /** "existing" to pick a customer, "new" to add one inline. */
    public string $customerMode = 'existing';

    public ?int $customerId = null;

    public string $customerSearch = '';

    public string $customerName = '';

    public string $customerPhone = '';

    public string $startDate = '';

    public string $dueDate = '';

    /** @var list<int> Units to be out or booked; when editing, the returned ones are not in here. */
    public array $unitIds = [];

    public string $eventName = '';

    public string $eventAddress = '';

    public string $eventSocial = '';

    /** A DiscountType value, or '' for no discount. Only used with the rentals.discount permission. */
    public string $discountType = '';

    /** Rupiah or percent, depending on $discountType, as typed. */
    public string $discountValue = '';

    public string $discountNote = '';

    public function mount(?Rental $rental = null): void
    {
        if ($rental?->exists) {
            if ($rental->isCompleted()) {
                session()->flash('status', $rental->isCancelled()
                    ? 'Penyewaan ini sudah dibatalkan, jadi tidak bisa diubah lagi.'
                    : 'Penyewaan ini sudah selesai, jadi tidak bisa diubah lagi.');
                $this->redirectRoute('rentals.show', $rental);

                return;
            }

            $this->rental = $rental;
            $this->customerId = $rental->customer_id;
            $this->startDate = $rental->start_date->toDateString();
            $this->dueDate = $rental->due_date->toDateString();
            $this->unitIds = $rental->openItems()->pluck('unit_id')->all();
            $this->eventName = $rental->event_name ?? '';
            $this->eventAddress = $rental->event_address ?? '';
            $this->eventSocial = $rental->event_social ?? '';
            $this->discountType = $rental->discount_type->value ?? '';
            $this->discountValue = $rental->discount_type ? (string) $rental->discount_value : '';
            $this->discountNote = $rental->discount_note ?? '';

            return;
        }

        if (Customer::query()->doesntExist() && Auth::user()->can(Permission::CreateCustomers)) {
            $this->customerMode = 'new';
        }
    }

    /**
     * Drop any selected units that stop being available after a date change.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['startDate', 'dueDate'], true)) {
            unset($this->availableUnits, $this->availableUnitsByCategory);
            $available = $this->availableUnits->modelKeys();
            $this->unitIds = array_values(array_filter($this->unitIds, fn (int|string $id): bool => in_array((int) $id, $available, true)));
        }
    }

    public function save(CreateRental $createRental, UpdateRental $updateRental): void
    {
        $this->authorize($this->rental ? Permission::EditRentals : Permission::CreateRentals);

        if ($this->customerMode === 'new') {
            $this->authorize(Permission::CreateCustomers);
        }

        $this->validate();

        $data = [
            'customer_id' => $this->customerMode === 'existing' ? $this->customerId : null,
            'customer_name' => trim($this->customerName),
            'customer_phone' => trim($this->customerPhone),
            'unit_ids' => array_map('intval', $this->unitIds),
            'start_date' => $this->startDate,
            'due_date' => $this->dueDate,
            'event_name' => $this->eventName,
            'event_address' => $this->eventAddress,
            'event_social' => $this->eventSocial,
        ];

        if (Auth::user()->can(Permission::GiveDiscounts)) {
            $data['discount'] = ['type' => $this->discountType, 'value' => (int) $this->discountValue, 'note' => $this->discountNote];
        }

        if ($this->rental) {
            $rental = $updateRental->handle(Auth::user(), $this->rental, $data);
            session()->flash('status', 'Perubahan penyewaan disimpan.');
        } else {
            $rental = $createRental->handle(Auth::user(), $data);
        }

        $this->redirectRoute('rentals.show', $rental);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $customerExists = Rule::exists(Customer::class, 'id');

        // The rental's own customer may have been deleted since; keeping them is still allowed.
        if ($this->customerId !== $this->rental?->customer_id) {
            $customerExists->withoutTrashed();
        }

        return [
            'customerMode' => ['required', Rule::in(['existing', 'new'])],
            'customerId' => ['nullable', 'required_if:customerMode,existing', $customerExists],
            'customerName' => ['required_if:customerMode,new', 'nullable', 'string', 'max:255'],
            'customerPhone' => ['required_if:customerMode,new', 'nullable', 'string', 'max:50'],
            'startDate' => ['required', 'date'],
            'dueDate' => ['required', 'date', 'after_or_equal:startDate'],
            'unitIds' => $this->returnedUnits->isEmpty() ? ['required', 'array', 'min:1'] : ['array'],
            'unitIds.*' => ['integer'],
            'eventName' => ['nullable', 'string', 'max:255'],
            'eventAddress' => ['nullable', 'string', 'max:255'],
            'eventSocial' => ['nullable', 'string', 'max:255'],
            'discountType' => ['nullable', Rule::enum(DiscountType::class)],
            'discountValue' => ['nullable', 'integer', 'min:0', $this->discountType === DiscountType::Percent->value ? 'max:100' : 'max:1000000000'],
            'discountNote' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'customerId.required_if' => 'Pilih pelanggan',
            'customerId.exists' => 'Pelanggan tersebut sudah tidak ada.',
            'customerName.required_if' => 'Nama pelanggan wajib diisi',
            'customerPhone.required_if' => 'Nomor HP wajib diisi',
            'startDate.required' => 'Pilih tanggal mulai',
            'dueDate.required' => 'Pilih tanggal kembali',
            'dueDate.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal mulai.',
            'unitIds.required' => 'Pilih minimal satu unit',
            'unitIds.min' => 'Pilih minimal satu unit',
            'discountValue.integer' => 'Diskon harus angka bulat.',
            'discountValue.max' => $this->discountType === DiscountType::Percent->value ? 'Diskon paling besar 100%.' : 'Diskon terlalu besar.',
        ];
    }

    #[Computed]
    public function datesAreValid(): bool
    {
        if (blank($this->startDate) || blank($this->dueDate)) {
            return false;
        }

        try {
            return CarbonImmutable::parse($this->dueDate)->gte(CarbonImmutable::parse($this->startDate));
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Inclusive number of rental days, or null until both dates are valid.
     */
    #[Computed]
    public function durationInDays(): ?int
    {
        if (! $this->datesAreValid) {
            return null;
        }

        return RentalBill::days(CarbonImmutable::parse($this->startDate), CarbonImmutable::parse($this->dueDate));
    }

    /**
     * Name shown in the summary for the customer picked or being typed in.
     */
    #[Computed]
    public function customerLabel(): ?string
    {
        $name = $this->customerMode === 'existing'
            ? Customer::withTrashed()->whereKey($this->customerId)->value('name')
            : trim($this->customerName);

        return filled($name) ? $name : null;
    }

    /**
     * The units currently ticked, for the summary panel.
     *
     * @return Collection<int, Unit>
     */
    #[Computed]
    public function selectedUnits(): Collection
    {
        return $this->availableUnits->whereIn('id', array_map('intval', $this->unitIds))->values();
    }

    /**
     * Rent for the units picked (plus any already returned) over the chosen dates, grouped by category.
     * Units already on the rental keep the rate they were booked at; others use their category's rate.
     *
     * @return array{lines: list<array{category: string, quantity: int, rate: int, amount: int}>, subtotal: int, discount: int, total: int}|null
     */
    #[Computed]
    public function estimate(): ?array
    {
        if (! $this->durationInDays) {
            return null;
        }

        $bookedRates = $this->rental ? $this->rental->items()->pluck('daily_rate', 'unit_id')->all() : [];

        $lines = $this->selectedUnits->concat($this->returnedUnits)
            ->map(fn (Unit $unit): array => ['category' => $unit->category->name, 'rate' => (int) ($bookedRates[$unit->id] ?? $unit->category->daily_rate)])
            ->groupBy(fn (array $unit): string => $unit['category'].'|'.$unit['rate'])
            ->map(fn ($units): array => [
                'category' => $units->first()['category'],
                'quantity' => $units->count(),
                'rate' => $units->first()['rate'],
                'amount' => $units->count() * $units->first()['rate'] * $this->durationInDays,
            ])
            ->sortBy([['category', 'asc'], ['rate', 'asc']])
            ->values()
            ->all();

        $subtotal = array_sum(array_column($lines, 'amount'));
        $discount = RentalBill::discountFor(DiscountType::tryFrom($this->discountType), (int) $this->discountValue, $subtotal);

        return ['lines' => $lines, 'subtotal' => $subtotal, 'discount' => $discount, 'total' => $subtotal - $discount];
    }

    /**
     * When editing, the units on this rental that have already come back. They stay on it as they are.
     *
     * @return SupportCollection<int, Unit>
     */
    #[Computed]
    public function returnedUnits(): SupportCollection
    {
        if (! $this->rental) {
            return collect();
        }

        return $this->rental->items()->whereNotNull('returned_at')->with('unit.category')->get()->pluck('unit')->sortBy('code')->values();
    }

    /**
     * Units free for the chosen range. When editing, this rental's own bookings don't block it and the
     * units it already has may stay even if their condition changed since. CreateRental and
     * UpdateRental re-check under a lock on submit.
     *
     * @return Collection<int, Unit>
     */
    #[Computed]
    public function availableUnits(): Collection
    {
        if (! $this->datesAreValid) {
            return new Collection;
        }

        $bookedUnitIds = $this->rental ? $this->rental->openItems()->pluck('unit_id')->all() : [];

        return Unit::query()
            ->where(fn (Builder $query) => $query->usable()->orWhereIn('units.id', $bookedUnitIds))
            ->freeBetween(
                CarbonImmutable::parse($this->startDate)->startOfDay(),
                CarbonImmutable::parse($this->dueDate)->endOfDay(),
                $this->rental,
            )
            ->with('category:id,name,daily_rate')
            ->orderBy('code')
            ->get(['id', 'category_id', 'code']);
    }

    /**
     * Available units grouped under their category name, categories in alphabetical order.
     *
     * @return SupportCollection<string, Collection<int, Unit>>
     */
    #[Computed]
    public function availableUnitsByCategory(): SupportCollection
    {
        return $this->availableUnits->groupBy('category.name')->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);
    }

    /**
     * @return Collection<int, Customer>
     */
    #[Computed]
    public function customers(): Collection
    {
        $search = trim($this->customerSearch);

        return Customer::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('phone', "%{$search}%")))
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);
    }

    public function render(): View
    {
        return view('livewire.rentals.form')->title($this->rental ? 'Ubah penyewaan' : 'Penyewaan baru');
    }
}
