<div 
    wire:poll.30s.visible="checkAndFetch"
    class="flex items-center gap-2 px-3 py-2 bg-[#eff1f5] text-[#4c4f69] font-mono border-l border-[#ccd0da]"
    x-data="{ showTooltip: false }"
>
    <style>
        .status-dot {
            @apply w-2 h-2 rounded-full;
        }
        .status-dot-active {
            @apply bg-[#df8e1d];
        }
        .status-dot-paused {
            @apply bg-[#df8e1d];
        }
        .status-dot-error {
            @apply bg-[#d20f39];
        }
        .status-dot-inactive {
            @apply bg-[#6c6f85];
        }
    </style>

    @if($isFetching)
        <div class="status-dot status-dot-active animate-pulse"></div>
        <span class="text-xs uppercase tracking-wider text-[#9ca0b0]">Fetching...</span>
    @elseif($lastError)
        <div class="status-dot status-dot-error"></div>
        <flux:tooltip :content="$lastError">
            <span class="text-xs uppercase tracking-wider text-[#d20f39]">Fetch Error</span>
        </flux:tooltip>
    @elseif($isQueueLocked)
        <div class="status-dot status-dot-paused"></div>
        <span class="text-xs uppercase tracking-wider text-[#df8e1d]">Paused</span>
    @elseif($isActive)
        <div class="status-dot status-dot-active"></div>
        <span class="text-xs text-[#9ca0b0]">{{ $lastFetchAt }}</span>
    @else
        <div class="status-dot status-dot-inactive"></div>
        <span class="text-xs uppercase tracking-wider text-[#9ca0b0]">Auto-Fetch Off</span>
    @endif
</div>
