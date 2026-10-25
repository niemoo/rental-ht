<?php

namespace App\Livewire\Customers;

use App\Enums\Permission;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Add a customer (customers.create) or change one's name and phone (customers.edit).
 */
class Form extends Component
{
    public ?Customer $customer = null;

    public string $name = '';

    public string $phone = '';

    public function mount(?Customer $customer = null): void
    {
        $this->customer = $customer?->exists ? $customer : null;
        $this->name = $this->customer->name ?? '';
        $this->phone = $this->customer->phone ?? '';
    }

    public function save(): void
    {
        $this->authorize($this->customer ? Permission::EditCustomers : Permission::CreateCustomers);

        $this->name = trim($this->name);
        $this->phone = trim($this->phone);

        $validated = $this->validate(
            ['name' => 'required|string|max:255', 'phone' => 'required|string|max:50'],
            ['name.required' => 'Nama wajib diisi', 'phone.required' => 'Nomor HP wajib diisi'],
        );

        if ($this->customer) {
            $this->customer->update($validated);
            session()->flash('status', "Pelanggan {$validated['name']} disimpan.");
            $this->redirectRoute('customers.show', $this->customer);

            return;
        }

        Customer::create($validated);

        session()->flash('status', "Pelanggan {$validated['name']} ditambahkan.");
        $this->redirectRoute('customers.index');
    }

    public function render(): View
    {
        return view('livewire.customers.form')->title($this->customer ? 'Ubah pelanggan' : 'Tambah pelanggan');
    }
}
