<div class="portal-card group overflow-hidden transition duration-200 hover:-translate-y-0.5 hover:border-orange-200 dark:hover:border-orange-400/30">
    <div class="relative aspect-[4/3] overflow-hidden bg-[#F0EBE4] dark:bg-[#0a1a28]">
        <img
            src="{{ $item->imageUrl() }}"
            alt="{{ $item->name }}"
            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
            loading="lazy"
        />
    </div>
    <div class="p-5">
        <h3 class="portal-heading font-display text-lg font-semibold">{{ $item->name }}</h3>
        <p class="portal-muted mt-1 line-clamp-2 text-sm leading-6">{{ $item->description }}</p>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <span class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $item->formattedPrice() }}</span>
            @php
                $activeTable = $currentTable ?? app(\App\Services\TableSessionService::class)->currentTablePayload();
            @endphp
            @if (! empty($activeTable))
                <form action="{{ route('cart.add') }}" method="post">
                    @csrf
                    <input type="hidden" name="menu_item_id" value="{{ $item->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-[10px] bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-400">
                        <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                        Add to Cart
                    </button>
                </form>
            @else
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-[10px] border border-[#E8E3DB] bg-white px-4 py-2 text-sm font-semibold text-[#16364D] shadow-sm transition hover:bg-[#F3F0EB] dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"
                    onclick="window.dispatchEvent(new CustomEvent('open-table-select'))"
                >
                    <i class="fa-solid fa-chair text-xs" aria-hidden="true"></i>
                    Select table first
                </button>
            @endif
        </div>
    </div>
</div>
