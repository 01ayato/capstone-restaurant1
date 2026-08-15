<?php

use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
};
?>

<div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3">
    <span class="text-sm text-zinc-500">{{ __('Livewire') }}</span>
    <span class="font-semibold tabular-nums text-zinc-900">{{ $count }}</span>
    <button
        type="button"
        wire:click="increment"
        class="inline-flex rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-800 shadow-sm transition hover:bg-zinc-50"
    >
        +1
    </button>
</div>
