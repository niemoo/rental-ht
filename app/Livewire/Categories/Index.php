<?php

namespace App\Livewire\Categories;

use App\Enums\Permission;
use App\Livewire\Concerns\SortsTable;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Categories: the kinds of equipment for rent (HT, mic, TOA…). Every unit belongs to one.
 */
#[Title('Kategori')]
class Index extends Component
{
    use SortsTable;

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: 'name')]
    public string $sortBy = 'name';

    #[Url(as: 'dir', except: 'asc')]
    public string $sortDirection = 'asc';

    /** Feedback shown above the list after a delete, e.g. why it was refused. */
    public ?string $notice = null;

    public function delete(int $categoryId): void
    {
        $this->authorize(Permission::DeleteCategories);

        $category = Category::query()->withCount('units')->findOrFail($categoryId);

        if (! $category->isDeletable()) {
            $this->notice = "Kategori {$category->name} masih punya {$category->units_count} unit. Pindahkan atau hapus unitnya dulu.";

            return;
        }

        $category->remove();

        $this->notice = "Kategori {$category->name} dihapus.";
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'rate' => 'daily_rate',
            'units' => 'units_count',
            'rented' => 'out_now_count',
            'available' => 'available_count',
        ];
    }

    public function render(): View
    {
        $search = trim($this->search);

        $categories = $this->paginateSorted(
            Category::query()
                ->withCount([
                    'units',
                    'units as out_now_count' => fn (Builder $query) => $query->outNow(),
                    'units as available_count' => fn (Builder $query) => $query->availableNow(),
                ])
                ->when($search !== '', fn (Builder $query) => $query->whereLike('name', "%{$search}%"))
        );

        return view('livewire.categories.index', ['categories' => $categories]);
    }
}
