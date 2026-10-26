@props(['label', 'value', 'icon' => null, 'sub' => null, 'trend' => null, 'tone' => 'primary'])

{{-- Velzon "card-animate" metric. A whole-number $value gets thousand dots. $trend: percentage change (int) or null, shown beside the label. $tone: a Bootstrap colour name for the icon. --}}
<div class="card card-animate mb-0 h-100">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2">
            <p class="flex-grow-1 text-uppercase fw-medium text-muted text-truncate mb-0">{{ $label }}</p>
            @if ($trend !== null)
                <h5 @class(['fs-14 mb-0 flex-shrink-0', 'text-success' => $trend >= 0, 'text-danger' => $trend < 0])>
                    <x-icon :name="$trend >= 0 ? 'arrow-up' : 'arrow-down'" class="fs-13 align-middle" />
                    {{ Number::format(abs($trend)) }}%
                </h5>
            @endif
        </div>
        <div class="d-flex align-items-end justify-content-between gap-2 mt-4">
            <div style="min-width: 0">
                <h4 class="fs-22 fw-semibold ff-secondary mb-2 tabular-nums text-truncate">{{ is_int($value) ? Number::format($value) : $value }}</h4>
                @if ($sub)
                    <span class="text-muted fs-13">{{ $sub }}</span>
                @endif
            </div>
            @if ($icon)
                <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-{{ $tone }}-subtle rounded fs-3"><x-icon :name="$icon" class="text-{{ $tone }}" /></span>
                </div>
            @endif
        </div>
    </div>
</div>
