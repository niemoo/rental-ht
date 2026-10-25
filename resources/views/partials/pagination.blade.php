{{-- Livewire pagination links for tables using App\Livewire\Concerns\SortsTable. --}}
@if ($paginator->total() > 0)
    @php($pageName = $paginator->getPageName())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-between gap-3 px-0.5 text-sm">
        <p class="text-xs tabular-nums text-muted-foreground">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
        </p>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-1">
                <x-button
                    variant="outline"
                    size="icon"
                    wire:click="previousPage('{{ $pageName }}')"
                    :disabled="$paginator->onFirstPage()"
                    aria-label="Halaman sebelumnya"
                >
                    <x-icon name="chevron-left" />
                </x-button>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-1.5 text-muted-foreground">{{ $element }}</span>
                    @else
                        @foreach ($element as $page => $url)
                            <x-button
                                wire:key="page-{{ $pageName }}-{{ $page }}"
                                :variant="$page === $paginator->currentPage() ? 'default' : 'ghost'"
                                size="icon"
                                class="tabular-nums"
                                wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                :aria-current="$page === $paginator->currentPage() ? 'page' : null"
                            >{{ $page }}</x-button>
                        @endforeach
                    @endif
                @endforeach

                <x-button
                    variant="outline"
                    size="icon"
                    wire:click="nextPage('{{ $pageName }}')"
                    :disabled="! $paginator->hasMorePages()"
                    aria-label="Halaman berikutnya"
                >
                    <x-icon name="chevron-right" />
                </x-button>
            </div>
        @endif
    </nav>
@endif
