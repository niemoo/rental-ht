<?php

namespace App\Livewire\Categories;

use App\Enums\Permission;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Add a category (categories.create) or change its name and daily rate (categories.edit).
 */
class Form extends Component
{
    public ?Category $category = null;

    public string $name = '';

    /** Price per unit per day in whole rupiah, as typed. */
    public string $dailyRate = '';

    public function mount(?Category $category = null): void
    {
        $this->category = $category?->exists ? $category : null;
        $this->name = $this->category->name ?? '';
        $this->dailyRate = $this->category ? (string) $this->category->daily_rate : '';
    }

    public function save(): void
    {
        $this->authorize($this->category ? Permission::EditCategories : Permission::CreateCategories);

        $this->name = trim($this->name);

        $this->validate(
            [
                'name' => ['required', 'string', 'max:50', Rule::unique(Category::class)->ignore($this->category?->id)],
                'dailyRate' => ['required', 'integer', 'min:0', 'max:1000000000'],
            ],
            [
                'name.required' => 'Nama kategori wajib diisi',
                'name.unique' => "Kategori \"{$this->name}\" sudah ada.",
                'dailyRate.required' => 'Tarif per hari wajib diisi',
                'dailyRate.integer' => 'Tarif harus angka bulat dalam rupiah.',
            ],
        );

        $validated = ['name' => $this->name, 'daily_rate' => (int) $this->dailyRate];

        if ($this->category) {
            $this->category->update($validated);
            session()->flash('status', "Kategori {$validated['name']} disimpan.");
        } else {
            Category::create($validated);
            session()->flash('status', "Kategori {$validated['name']} ditambahkan.");
        }

        $this->redirectRoute('categories.index');
    }

    public function render(): View
    {
        return view('livewire.categories.form')->title($this->category ? 'Ubah kategori' : 'Tambah kategori');
    }
}
