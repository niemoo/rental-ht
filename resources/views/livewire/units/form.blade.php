<div class="vstack gap-4">
    <x-page-header :title="$unit ? 'Ubah unit '.$unit->code : 'Tambah unit'" :subtitle="$unit ? 'Ganti kategori, kode, atau kondisi unit ini.' : 'Daftarkan satu unit baru ke armada.'">
        <x-slot:action>
            <x-button variant="outline" :href="route('units.index')"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    <div class="row">
        <div class="col-xl-6 col-lg-8">
            <div class="vstack gap-3">
                <x-notice :message="$notice" />

                <form
                    x-data="confirmAction(@js([
                        'text' => $unit ? 'Perubahan pada unit '.$unit->code.' akan disimpan.' : 'Unit baru akan ditambahkan ke daftar unit.',
                        'confirmLabel' => $unit ? 'Ya, simpan perubahan' : 'Ya, simpan unit',
                    ]))"
                    x-on:submit.prevent="ask('save')"
                    class="card mb-0"
                >
                    <div class="card-header">
                        <h5 class="card-title mb-0">Detail unit</h5>
                    </div>

                    <div class="card-body vstack gap-3">
                        <x-field label="Kategori" for="unit-category" error="categoryId">
                            @if ($this->categories->isEmpty())
                                <p class="text-muted mb-0">
                                    Belum ada kategori.
                                    @can(\App\Enums\Permission::CreateCategories)
                                        <a href="{{ route('categories.create') }}" class="fw-medium link-primary">Buat kategori dulu</a>.
                                    @endcan
                                </p>
                            @else
                                <x-select id="unit-category" wire:model="categoryId" :options="$this->categories->pluck('name', 'id')" :searchable="$this->categories->count() > 6" />
                            @endif
                        </x-field>

                        <x-field label="Kode" for="unit-code" error="code" hint="Label yang disebut staf, misalnya A-25 atau MIC-03.">
                            <x-input id="unit-code" wire:model="code" placeholder="cth. A-25" autofocus />
                        </x-field>

                        <x-field label="Kondisi" for="unit-condition" error="condition" hint="Hanya unit berkondisi Baik yang bisa dipilih saat membuat penyewaan.">
                            <x-select id="unit-condition" wire:model="condition" :options="\App\Enums\UnitCondition::options()" />
                        </x-field>
                    </div>

                    <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                        <x-button variant="ghost" :href="route('units.index')">Batal</x-button>
                        @unless ($unit)
                            <x-button variant="outline" x-on:click="ask('save', true)" wire:loading.attr="disabled" wire:target="save">Simpan &amp; tambah lagi</x-button>
                        @endunless
                        <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $unit ? 'Simpan perubahan' : 'Simpan unit' }}</span>
                            <span wire:loading wire:target="save">Menyimpan…</span>
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
