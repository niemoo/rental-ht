<?php

namespace App\Livewire\Customers;

use App\Enums\Permission;
use App\Livewire\Concerns\SortsTable;
use App\Models\Customer;
use App\Models\Rental;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * One customer's details and every rental they have made, newest first by default.
 */
class Show extends Component
{
    use SortsTable;

    public Customer $customer;

    #[Url(as: 'sort', except: 'start')]
    public string $sortBy = 'start';

    #[Url(as: 'dir', except: 'desc')]
    public string $sortDirection = 'desc';

    /** Why a delete was refused. */
    public ?string $notice = null;

    public function delete(): void
    {
        $this->authorize(Permission::DeleteCustomers);

        if (! $this->customer->isDeletable()) {
            $this->notice = "Pelanggan {$this->customer->name} masih punya penyewaan yang belum selesai. Selesaikan penyewaannya dulu.";

            return;
        }

        $this->customer->remove();

        session()->flash('status', "Pelanggan {$this->customer->name} dihapus.");
        $this->redirectRoute('customers.index');
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'start' => 'start_date',
            'due' => 'due_date',
            'event' => 'event_name',
            'units' => 'items_count',
        ];
    }

    public function render(): View
    {
        $this->customer->loadCount([
            'rentals',
            'rentals as active_rentals_count' => fn (Builder $query) => $query->open(),
            'openRentalItems as units_out_count',
        ]);

        $rentals = $this->paginateSorted(
            Rental::query()
                ->whereBelongsTo($this->customer)
                ->with('openItems')
                ->withCount('items')
                ->withMax('items', 'returned_at')
        );

        return view('livewire.customers.show', ['rentals' => $rentals])->title($this->customer->name);
    }
}
