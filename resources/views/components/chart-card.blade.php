@props(['title', 'subtitle' => null])

<div class="card mb-0 h-100">
    <div class="card-header">
        <h4 class="card-title mb-0">{{ $title }}</h4>
        @if ($subtitle)
            <p class="text-muted fs-13 mb-0 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="card-body">{{ $slot }}</div>
</div>
