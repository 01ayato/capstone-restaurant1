@php
    $initialOpen = $initialOpen ?? false;
    $icons = [
        'folder' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
        'folder-open' => 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.116 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V9A2.25 2.25 0 015.25 7.5h13.5A2.25 2.25 0 0121.25 9v.776',
    ];
@endphp
<span class="relative inline-flex size-5 shrink-0 opacity-80">
    @if ($initialOpen)
        <svg
            class="size-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['folder-open'] }}" />
        </svg>
    @else
        <svg
            x-show="! open"
            class="size-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['folder'] }}" />
        </svg>
        <svg
            x-show="open"
            style="display: none"
            class="size-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['folder-open'] }}" />
        </svg>
    @endif
</span>
