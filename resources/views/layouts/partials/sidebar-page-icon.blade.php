@php
    $raw = isset($icon) ? trim((string) $icon) : '';
    $safe = $raw !== '' ? preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $raw) : '';
    $safe = preg_replace('/\s+/', ' ', trim($safe));

    if ($safe !== '' && ! str_contains($safe, 'fa-solid') && ! str_contains($safe, 'fa-regular') && ! str_contains($safe, 'fa-brands')) {
        if (str_starts_with($safe, 'fas ')) {
            $safe = 'fa-solid '.trim(substr($safe, 4));
        } elseif (str_starts_with($safe, 'far ')) {
            $safe = 'fa-regular '.trim(substr($safe, 4));
        } elseif (str_starts_with($safe, 'fab ')) {
            $safe = 'fa-brands '.trim(substr($safe, 4));
        } elseif (str_starts_with($safe, 'fa-')) {
            $safe = 'fa-solid '.$safe;
        }
    }

    $fallbackIcon = $fallback ?? 'document';
@endphp
@if ($safe !== '')
    <span class="inline-flex size-5 shrink-0 items-center justify-center text-[0.9375rem] leading-none opacity-80 in-data-sidebar-active:text-white in-data-sidebar-active:opacity-100" aria-hidden="true">
        <i class="{{ e($safe) }}"></i>
    </span>
@else
    @include('layouts.partials.sidebar-icon', ['icon' => $fallbackIcon])
@endif
