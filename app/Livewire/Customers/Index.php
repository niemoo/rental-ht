<?php

namespace App\Livewire\Customers;

use App\Enums\Permission;
use App\Livewire\Concerns\SortsTable;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Pelanggan')]
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

    public function delete(int $customerId): void
    {
        $this->authorize(Permission::DeleteCustomers);

        $customer = Customer::query()->findOrFail($customerId);

        if (! $customer->isDeletable()) {
            $this->notice = "Pelanggan {$customer->name} masih punya penyewaan yang belum selesai. Selesaikan penyewaannya dulu.";

            return;
        }

        $customer->remove();

        $this->notice = "Pelanggan {$customer->name} dihapus.";
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'phone' => 'phone',
            'active' => 'active_rentals_count',
            'units' => 'units_out_count',
        ];
    }

    public function render(): View
    {
        $search = trim($this->search);

        $customers = $this->paginateSorted(
            Customer::query()
                ->withCount([
                    'rentals as active_rentals_count' => fn (Builder $query) => $query->open(),
                    'openRentalItems as units_out_count',
                ])
                ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                    ->whereLike('name', "%{$search}%")
                    ->orWhereLike('phone', "%{$search}%")))
        );

        return view('livewire.customers.index', [
            'customers' => $customers,
            'totalCount' => Customer::query()->count(),
            'withActiveCount' => Customer::query()->whereHas('rentals', fn (Builder $query) => $query->open())->count(),
        ]);
    }
}
