@props(['title', 'subtitle' => null, 'action' => null])

{{-- Velzon's page title bar, which spans the top of the content area. --}}
<div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-3 mb-0">
    <div style="min-width: 0">
        <h4 class="mb-0">{{ $title }}</h4>
        @if ($subtitle)
            <p class="text-muted fs-13 mb-0 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($action)
        <div class="d-flex flex-wrap align-items-center gap-2">{{ $action }}</div>
    @endif
</div>
