{{--
    Livewire pagination links for tables using App\Livewire\Concerns\SortsTable, in Velzon's pagination style,
    with the rows-per-page picker bound to the component's $perPage. The picker hides while every row fits the smallest size.
--}}
@if ($paginator->total() > 0)
    @php
        $pageName = $paginator->getPageName();
        $pageSizes = \App\Support\PageSize::OPTIONS;
    @endphp
    <nav role="navigation" aria-label="Navigasi halaman" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            @if ($paginator->total() > $pageSizes[0] || $paginator->perPage() !== $pageSizes[0])
                <div class="d-flex align-items-center gap-2 text-muted fs-13">
                    <span>Tampilkan</span>
                    <div style="width: 5.5rem">
                        <x-select size="sm" wire:model.live="perPage" :options="array_combine($pageSizes, $pageSizes)" aria-label="Baris per halaman" />
                    </div>
                    <span>baris</span>
                </div>
            @endif
            <p class="text-muted fs-13 tabular-nums mb-0">
                Menampilkan <span class="fw-semibold">{{ Number::format($paginator->firstItem()) }}–{{ Number::format($paginator->lastItem()) }}</span> dari <span class="fw-semibold">{{ Number::format($paginator->total()) }}</span>
            </p>
        </div>

        @if ($paginator->hasPages())
            <ul class="pagination pagination-separated pagination-sm flex-wrap mb-0">
                <li @class(['page-item', 'disabled' => $paginator->onFirstPage()])>
                    <button type="button" class="page-link" wire:click="previousPage('{{ $pageName }}')" @disabled($paginator->onFirstPage()) aria-label="Halaman sebelumnya">
                        <x-icon name="chevron-left" />
                    </button>
                </li>

                {{-- Phones get "6 / 11" between the arrows; the numbered links only fit from the sm breakpoint up. --}}
                <li class="page-item disabled d-sm-none">
                    <span class="page-link text-body tabular-nums">{{ Number::format($paginator->currentPage()) }} / {{ Number::format($paginator->lastPage()) }}</span>
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled d-none d-sm-block"><span class="page-link">{{ $element }}</span></li>
                    @else
                        @foreach ($element as $page => $url)
                            <li wire:key="page-{{ $pageName }}-{{ $page }}" @class(['page-item d-none d-sm-block', 'active' => $page === $paginator->currentPage()])>
                                <button
                                    type="button"
                                    class="page-link tabular-nums"
                                    wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                    @if ($page === $paginator->currentPage()) aria-current="page" @endif
                                >{{ Number::format($page) }}</button>
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li @class(['page-item', 'disabled' => ! $paginator->hasMorePages()])>
                    <button type="button" class="page-link" wire:click="nextPage('{{ $pageName }}')" @disabled(! $paginator->hasMorePages()) aria-label="Halaman berikutnya">
                        <x-icon name="chevron-right" />
                    </button>
                </li>
            </ul>
        @endif
    </nav>
@endif
