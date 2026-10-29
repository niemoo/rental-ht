<?php

namespace App\Livewire\Units;

use App\Enums\Permission;
use App\Enums\UnitCondition;
use App\Livewire\Concerns\SortsTable;
use App\Models\Category;
use App\Models\RentalItem;
use App\Models\Unit;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Units table. Kategori, Kondisi, Status and Selesai are filtered from popups on their column
 * headers (multi-select; an empty selection means no filter).
 */
#[Title('Unit')]
class Index extends Component
{
    use SortsTable;

    /** Status filter options: what the unit is doing right now. */
    private const STATUSES = ['available' => 'Tersedia', 'rented' => 'Disewa', 'unusable' => 'Tidak tersedia'];

    /** Selesai (due date) filter options; both only match units that are out now. */
    private const DUE_STATES = ['overdue' => 'Terlambat', 'on_time' => 'Tepat waktu'];

    #[Url(except: '')]
    public string $search = '';

    /** @var list<string> Category ids. */
    #[Url(as: 'categories', except: [])]
    public array $categoryIds = [];

    /** @var list<string> UnitCondition values. */
    #[Url(except: [])]
    public array $conditions = [];

    /** @var list<string> Keys of {@see self::STATUSES}. */
    #[Url(as: 'status', except: [])]
    public array $statuses = [];

    /** @var list<string> Keys of {@see self::DUE_STATES}. */
    #[Url(as: 'due', except: [])]
    public array $dueStates = [];

    #[Url(as: 'sort', except: 'code')]
    public string $sortBy = 'code';

    #[Url(as: 'dir', except: 'asc')]
    public string $sortDirection = 'asc';

    /** Feedback shown above the list after a delete, e.g. why it was refused. */
    public ?string $notice = null;

    public function delete(int $unitId): void
    {
        $this->authorize(Permission::DeleteUnits);

        $unit = Unit::query()->findOrFail($unitId);

        if (! $unit->isDeletable()) {
            $this->notice = "Unit {$unit->code} masih disewa atau sudah dipesan. Selesaikan penyewaannya dulu sebelum menghapus.";

            return;
        }

        $unit->remove();

        $this->notice = "Unit {$unit->code} dihapus.";
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'code' => 'units.code',
            'category' => 'category_name',
            'condition' => 'condition_rank',
            'status' => 'is_rented',
            'renter' => 'current_renter',
            'due' => 'current_due_date',
        ];
    }

    public function render(): View
    {
        $now = now();

        $units = $this->paginateSorted(
            $this->filtered(Unit::query(), $now)
                ->select('units.*')
                ->addSelect([
                    'category_name' => Category::query()->select('name')->whereColumn('categories.id', 'units.category_id'),
                    'current_renter' => $this->currentItem($now)->join('customers', 'customers.id', '=', 'rentals.customer_id')->select('customers.name'),
                    'current_due_date' => $this->currentItem($now)->select('rentals.due_date'),
                ])
                ->selectRaw($this->conditionRankSql().' as condition_rank')
                ->withExists(['openRentalItems as is_rented' => fn (Builder $item) => $item->whereHas('rental', fn (Builder $rental) => $rental->where('start_date', '<=', $now))])
                ->with(['category:id,name', 'openRentalItems.rental.customer'])
        );

        $categoryOptions = Category::query()->orderBy('name')->pluck('name', 'id')->all();

        return view('livewire.units.index', [
            'units' => $units->through(fn (Unit $unit): array => [
                'id' => $unit->id,
                'code' => $unit->code,
                'category' => $unit->category->name,
                'condition' => $unit->condition,
                'current' => $unit->openRentalItems->first(fn (RentalItem $item): bool => $item->rental->start_date->lte($now)),
                'upcoming_count' => $unit->openRentalItems->filter(fn (RentalItem $item): bool => $item->rental->start_date->gt($now))->count(),
            ]),
            'summary' => [
                'all' => Unit::query()->count(),
                'available' => Unit::query()->availableNow($now)->count(),
            ],
            'filters' => [
                'categoryIds' => [
                    'options' => $categoryOptions,
                    'counts' => $this->filtered(Unit::query(), $now, except: 'categoryIds')
                        ->toBase()->select('category_id')->selectRaw('count(*) as aggregate')->groupBy('category_id')
                        ->pluck('aggregate', 'category_id')->all(),
                ],
                'conditions' => [
                    'options' => UnitCondition::options(),
                    'counts' => $this->filtered(Unit::query(), $now, except: 'conditions')
                        ->toBase()->select('units.condition')->selectRaw('count(*) as aggregate')->groupBy('units.condition')
                        ->pluck('aggregate', 'condition')->all(),
                ],
                'statuses' => [
                    'options' => self::STATUSES,
                    'counts' => collect(self::STATUSES)->map(fn (string $label, string $status): int => $this->filtered(Unit::query(), $now, except: 'statuses')
                        ->where(fn (Builder $query) => $this->constrainToStatus($query, $status, $now))->count())->all(),
                ],
                'dueStates' => [
                    'options' => self::DUE_STATES,
                    'counts' => collect(self::DUE_STATES)->map(fn (string $label, string $state): int => $this->filtered(Unit::query(), $now, except: 'dueStates')
                        ->where(fn (Builder $query) => $this->constrainToDueState($query, $state, $now))->count())->all(),
                ],
            ],
            'singleCategoryId' => count($this->selected('categoryIds')) === 1 ? $this->selected('categoryIds')[0] : null,
        ]);
    }

    /**
     * Apply the search and every column filter, optionally leaving one out (used for that filter's own counts).
     *
     * @param  Builder<Unit>  $query
     * @return Builder<Unit>
     */
    private function filtered(Builder $query, CarbonInterface $now, ?string $except = null): Builder
    {
        $search = trim($this->search);

        return $query
            ->when($except !== 'categoryIds' && $this->selected('categoryIds') !== [], fn (Builder $query) => $query->whereIn('units.category_id', $this->selected('categoryIds')))
            ->when($except !== 'conditions' && $this->selected('conditions') !== [], fn (Builder $query) => $query->whereIn('units.condition', $this->selected('conditions')))
            ->when($except !== 'statuses' && $this->selected('statuses') !== [], fn (Builder $query) => $query->where(function (Builder $query) use ($now): void {
                foreach ($this->selected('statuses') as $status) {
                    $query->orWhere(fn (Builder $query) => $this->constrainToStatus($query, $status, $now));
                }
            }))
            ->when($except !== 'dueStates' && $this->selected('dueStates') !== [], fn (Builder $query) => $query->where(function (Builder $query) use ($now): void {
                foreach ($this->selected('dueStates') as $state) {
                    $query->orWhere(fn (Builder $query) => $this->constrainToDueState($query, $state, $now));
                }
            }))
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search, $now));
    }

    /**
     * The valid values of a filter property; anything else (e.g. edited into the URL) is dropped.
     *
     * @param  'categoryIds'|'conditions'|'statuses'|'dueStates'  $filter
     * @return list<int|string>
     */
    private function selected(string $filter): array
    {
        /** @var Collection<int, mixed> $values */
        $values = collect($this->{$filter})->filter(fn (mixed $value): bool => is_string($value) || is_int($value))->map(fn (int|string $value): string => (string) $value);

        return match ($filter) {
            'categoryIds' => $values->filter(fn (string $id): bool => ctype_digit($id))->map(fn (string $id): int => (int) $id)->unique()->values()->all(),
            'conditions' => $values->filter(fn (string $value): bool => UnitCondition::tryFrom($value) !== null)->unique()->values()->all(),
            'statuses' => $values->intersect(array_keys(self::STATUSES))->values()->all(),
            'dueStates' => $values->intersect(array_keys(self::DUE_STATES))->values()->all(),
        };
    }

    /**
     * @param  Builder<Unit>  $query
     */
    private function constrainToStatus(Builder $query, string $status, CarbonInterface $now): void
    {
        match ($status) {
            'available' => $query->availableNow($now),
            'rented' => $query->outNow($now),
            'unusable' => $query->outOfService($now),
        };
    }

    /**
     * @param  Builder<Unit>  $query
     */
    private function constrainToDueState(Builder $query, string $state, CarbonInterface $now): void
    {
        match ($state) {
            'overdue' => $query->overdue($now),
            'on_time' => $query->outOnTime($now),
        };
    }

    /**
     * SQL that turns units.condition into its {@see UnitCondition::rank()}, so sorting follows
     * Baik → Dalam perbaikan → Rusak → Hilang instead of the alphabet. Values come from the enum, not input.
     */
    private function conditionRankSql(): string
    {
        $cases = collect(UnitCondition::cases())
            ->map(fn (UnitCondition $condition): string => "WHEN '{$condition->value}' THEN {$condition->rank()}")
            ->implode(' ');

        return "CASE units.condition {$cases} END";
    }

    /**
     * The unreturned item a unit is physically out on right now, joined to its rental.
     *
     * @return Builder<RentalItem>
     */
    private function currentItem(CarbonInterface $now): Builder
    {
        return RentalItem::query()
            ->join('rentals', 'rentals.id', '=', 'rental_items.rental_id')
            ->whereColumn('rental_items.unit_id', 'units.id')
            ->whereNull('rental_items.returned_at')
            ->whereNull('rentals.deleted_at')
            ->where('rentals.start_date', '<=', $now)
            ->orderBy('rentals.start_date')
            ->limit(1);
    }

    /**
     * Match the code, the category name, or the customer who has the unit right now.
     *
     * @param  Builder<Unit>  $query
     */
    private function applySearch(Builder $query, string $search, CarbonInterface $now): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('units.code', "%{$search}%")
            ->orWhereHas('category', fn (Builder $category) => $category->whereLike('name', "%{$search}%"))
            ->orWhereHas('openRentalItems.rental', fn (Builder $rental) => $rental
                ->where('start_date', '<=', $now)
                ->whereHas('customer', fn (Builder $customer) => $customer->whereLike('name', "%{$search}%"))));
    }
}
