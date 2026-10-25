@props(['category', 'canViewUnits', 'canEdit', 'canDelete'])

{{-- Three-dot menu for one row on the Categories page; "Detail" opens its units. Expects units_count on $category. --}}
<x-row-actions
    :label="'kategori '.$category->name"
    :detail-href="$canViewUnits ? route('units.index', ['categories' => [$category->id]]) : null"
    :edit-href="$canEdit ? route('categories.edit', $category) : null"
    :delete-action="$canDelete ? 'delete('.$category->id.')' : null"
    delete-title="Hapus kategori"
    delete-button="Hapus kategori"
    :delete-blocked="$category->units_count > 0"
    {{ $attributes }}
>
    @if ($category->units_count)
        Kategori <strong class="text-foreground">{{ $category->name }}</strong> masih punya {{ $category->units_count }} unit, jadi belum bisa dihapus. Pindahkan unitnya ke kategori lain atau hapus unitnya dulu.
    @else
        Hapus kategori <strong class="text-foreground">{{ $category->name }}</strong>?
    @endif
</x-row-actions>
