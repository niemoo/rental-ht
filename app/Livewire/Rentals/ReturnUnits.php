<?php

namespace App\Livewire\Rentals;

use App\Actions\ReturnRental;
use App\Enums\Permission;
use App\Enums\UnitCondition;
use App\Models\Rental;
use App\Models\RentalItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Take back every unit still out on a rental (rentals.return), recording the condition each one came back in.
 * A page rather than a dialog, so a rental with many units stays easy to go through.
 */
class ReturnUnits extends Component
{
    public Rental $rental;

    /** @var array<int, string> UnitCondition values keyed by rental item id; each starts at the unit's current condition. */
    public array $conditions = [];

    public function mount(Rental $rental): void
    {
        $this->rental = $rental;

        if ($rental->isCompleted()) {
            session()->flash('status', $rental->isCancelled()
                ? 'Penyewaan ini sudah dibatalkan, jadi tidak ada unit yang perlu dikembalikan.'
                : 'Semua unit di penyewaan ini sudah dikembalikan.');
            $this->redirectRoute('rentals.show', $rental);

            return;
        }

        $this->conditions = $this->openItems
            ->mapWithKeys(fn (RentalItem $item): array => [$item->id => $item->unit->condition->value])
            ->all();
    }

    /**
     * Give every unit the same condition, e.g. when the whole set came back fine.
     */
    public function setAllConditions(string $condition): void
    {
        if (UnitCondition::tryFrom($condition)) {
            $this->conditions = array_fill_keys(array_keys($this->conditions), $condition);
        }
    }

    public function save(ReturnRental $returnRental): void
    {
        $this->authorize(Permission::ReturnRentals);

        $this->validate(
            ['conditions' => ['array'], 'conditions.*' => ['required', Rule::enum(UnitCondition::class)]],
            ['conditions.*' => 'Pilih kondisi unit.'],
        );

        $count = $this->openItems->count();
        $returnRental->handle(Auth::user(), $this->rental, $this->conditions);

        session()->flash('status', Number::format($count).' unit dikembalikan.');
        $this->redirectRoute('rentals.show', $this->rental);
    }

    /**
     * The units still out, by unit code.
     *
     * @return Collection<int, RentalItem>
     */
    #[Computed]
    public function openItems(): Collection
    {
        return $this->rental->openItems()->with('unit.category')->get()->sortBy('unit.code', SORT_NATURAL)->values();
    }

    /**
     * How many units come back in each condition, for the summary; conditions nobody picked are left out.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function conditionCounts(): array
    {
        $counts = array_count_values(array_map('strval', $this->conditions));

        return collect(UnitCondition::cases())
            ->mapWithKeys(fn (UnitCondition $condition): array => [$condition->value => $counts[$condition->value] ?? 0])
            ->filter()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.rentals.return-units')->title('Kembalikan unit');
    }
}
