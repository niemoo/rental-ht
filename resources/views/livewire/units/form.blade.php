<div class="flex flex-col gap-4">
    <div>
        <a href="{{ route('units.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <x-icon name="arrow-left" /> Kembali
        </a>
        <h1 class="text-xl font-semibold tracking-tight lg:text-2xl">{{ $unit ? 'Ubah unit '.$unit->code : 'Tambah unit' }}</h1>
    </div>

    @if ($notice)
        <div role="status" class="flex max-w-xl items-center justify-between gap-3 rounded-lg border border-border bg-card px-4 py-2.5 text-sm shadow-xs">
            <span>{{ $notice }}</span>
            <button type="button" wire:click="$set('notice', null)" class="text-muted-foreground hover:text-foreground" aria-label="Tutup">
                <x-icon name="x" />
            </button>
        </div>
    @endif

    <form wire:submit="save" class="flex max-w-xl flex-col gap-4 rounded-xl border border-border bg-card p-4">
        <x-field label="Kategori" for="unit-category" error="categoryId">
            @if ($this->categories->isEmpty())
                <p class="text-sm text-muted-foreground">
                    Belum ada kategori.
                    @can(\App\Enums\Permission::CreateCategories)
                        <a href="{{ route('categories.create') }}" class="font-medium text-primary hover:underline">Buat kategori dulu</a>.
                    @endcan
                </p>
            @else
                <select id="unit-category" wire:model="categoryId" class="h-9 w-full rounded-lg border border-input bg-white px-2.5 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30">
                    @foreach ($this->categories as $categoryOption)
                        <option value="{{ $categoryOption->id }}" wire:key="category-option-{{ $categoryOption->id }}">{{ $categoryOption->name }}</option>
                    @endforeach
                </select>
            @endif
        </x-field>

        <x-field label="Kode" for="unit-code" error="code" hint="Label yang disebut staf, misalnya A-25 atau MIC-03.">
            <x-input id="unit-code" wire:model="code" placeholder="cth. A-25" autofocus />
        </x-field>
        <x-field label="Kondisi" for="unit-condition" error="condition" hint="Hanya unit berkondisi Baik yang bisa dipilih saat membuat penyewaan.">
            <select id="unit-condition" wire:model="condition" class="h-9 w-full rounded-lg border border-input bg-white px-2.5 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30">
                @foreach (\App\Enums\UnitCondition::options() as $value => $label)
                    <option value="{{ $value }}" wire:key="condition-option-{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-border pt-4">
            <x-button variant="ghost" :href="route('units.index')">Batal</x-button>
            @unless ($unit)
                <x-button variant="outline" wire:click="save(true)" wire:loading.attr="disabled" wire:target="save">Simpan & tambah lagi</x-button>
            @endunless
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $unit ? 'Simpan perubahan' : 'Simpan unit' }}</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </x-button>
        </div>
    </form>
</div>
