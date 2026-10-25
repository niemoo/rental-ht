<?php

namespace App\Livewire\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\WithPagination;

/**
 * Paginated table with clickable column sorting.
 *
 * The component declares `public string $sortBy` and `public string $sortDirection` itself (as #[Url]
 * properties, so each table picks its own default) and lists which columns may be sorted.
 * Values coming from the URL are checked against that list, so they never reach SQL unfiltered.
 */
trait SortsTable
{
    use WithPagination;

    /**
     * Sort keys this table accepts, mapped to the column or select alias they order by.
     *
     * @return array<string, string>
     */
    abstract protected function sortableColumns(): array;

    /**
     * Header click: sort by a new column ascending, or flip the direction of the current one.
     */
    public function sort(string $column): void
    {
        if (! array_key_exists($column, $this->sortableColumns())) {
            return;
        }

        $this->sortDirection = $this->sortBy === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
        $this->resetPage();
    }

    /**
     * Mobile sort picker: set column and direction at once.
     */
    public function sortTo(string $column, string $direction): void
    {
        if (! array_key_exists($column, $this->sortableColumns())) {
            return;
        }

        $this->sortBy = $column;
        $this->sortDirection = $direction === 'desc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    /**
     * A changed search or filter shows a different set of rows, so start again from page one.
     */
    public function updatedSortsTable(string $property): void
    {
        // Array filters report "conditions" or "conditions.0", so compare the top-level name.
        if (in_array(Str::before($property, '.'), ['search', 'filter', 'category', 'categoryIds', 'conditions', 'statuses', 'dueStates', 'paymentStatuses', 'period', 'from', 'to', 'methods', 'agings'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Order by the chosen column, then by primary key so rows never shift between pages.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applySort(Builder $query): Builder
    {
        $columns = $this->sortableColumns();
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $query
            ->orderBy($columns[$this->sortBy] ?? reset($columns), $direction)
            ->orderBy($query->getModel()->getQualifiedKeyName(), $direction);

        return $query;
    }

    /**
     * Sort and paginate. If the current page no longer exists (rows were removed or returned),
     * fall back to the last page that does.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return LengthAwarePaginator<int, TModel>
     */
    protected function paginateSorted(Builder $query): LengthAwarePaginator
    {
        $query = $this->applySort($query);
        $page = (clone $query)->paginate($this->perPage());

        if ($page->isEmpty() && $page->currentPage() > 1) {
            $this->setPage(max($page->lastPage(), 1));
            $page = $query->paginate($this->perPage());
        }

        return $page;
    }

    protected function perPage(): int
    {
        return 15;
    }

    public function paginationView(): string
    {
        return 'partials.pagination';
    }
}
