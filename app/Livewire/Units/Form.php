<?php

namespace App\Livewire\Units;

use App\Enums\Permission;
use App\Enums\UnitCondition;
use App\Models\Category;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Add a unit (units.create) or change one's category and code (units.edit).
 */
class Form extends Component
{
    public ?Unit $unit = null;

    public ?int $categoryId = null;

    public string $code = '';

    /** A UnitCondition value. */
    public string $condition = 'good';

    /** Confirmation shown after "Simpan & tambah lagi". */
    public ?string $notice = null;

    /**
     * When adding, preselect the category the user was looking at (?category=id), else the first one.
     */
    public function mount(?Unit $unit = null): void
    {
        if ($unit?->exists) {
            $this->unit = $unit;
            $this->categoryId = $unit->category_id;
            $this->code = $unit->code;
            $this->condition = $unit->condition->value;

            return;
        }

        $requested = (int) request()->query('category');

        $this->categoryId = $this->categories->firstWhere('id', $requested)?->id ?? $this->categories->first()?->id;
    }

    /**
     * Save, then go back to the list — or, when adding several in a row, stay here with the category kept.
     */
    public function save(bool $addAnother = false): void
    {
        $this->authorize($this->unit ? Permission::EditUnits : Permission::CreateUnits);

        $this->code = trim($this->code);

        $validated = $this->validate(
            [
                'categoryId' => ['required', 'integer', Rule::exists(Category::class, 'id')->withoutTrashed()],
                'code' => ['required', 'string', 'max:50', Rule::unique(Unit::class)->ignore($this->unit?->id)],
                'condition' => ['required', Rule::enum(UnitCondition::class)],
            ],
            [
                'categoryId.required' => 'Pilih kategori',
                'categoryId.exists' => 'Kategori tersebut sudah tidak ada.',
                'code.required' => 'Kode wajib diisi',
                'code.unique' => "Kode unit \"{$this->code}\" sudah dipakai, termasuk oleh unit yang sudah dihapus.",
            ],
        );

        $attributes = ['category_id' => $validated['categoryId'], 'code' => $validated['code'], 'condition' => $validated['condition']];

        if ($this->unit) {
            $this->unit->update($attributes);
            session()->flash('status', "Unit {$validated['code']} disimpan.".$this->bookingWarning($this->unit));
            $this->redirectRoute('units.index');

            return;
        }

        Unit::create($attributes);
        $message = "Unit {$validated['code']} ditambahkan.";

        if ($addAnother) {
            $this->reset('code');
            $this->notice = $message;

            return;
        }

        session()->flash('status', $message);
        $this->redirectRoute('units.index');
    }

    /**
     * A unit taken out of service keeps its bookings; tell staff so they can swap it out.
     */
    private function bookingWarning(Unit $unit): string
    {
        if ($unit->condition->isUsable()) {
            return '';
        }

        $bookings = $unit->openRentalItems()->count();

        return $bookings > 0
            ? ' Perhatian: unit ini '.mb_strtolower($unit->condition->label())." tapi masih ada di {$bookings} penyewaan yang belum selesai. Cek penyewaannya dan ganti unitnya bila perlu."
            : '';
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    public function render(): View
    {
        return view('livewire.units.form')->title($this->unit ? 'Ubah unit' : 'Tambah unit');
    }
}
