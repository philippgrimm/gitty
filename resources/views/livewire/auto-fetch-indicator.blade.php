<div 
    wire:poll.30s.visible="checkAndFetch"
    class="flex items-center gap-2 px-3 py-2 bg-zinc-950 text-zinc-100 font-mono border-l-2 border-zinc-800"
    x-data="{ showTooltip: false }"
>
    <style>
        .status-dot {
            @apply w-2 h-2 rounded-full;
        }
        .status-dot-active {
            @apply bg-green-500;
        }
        .status-dot-paused {
            @apply bg-yellow-500;
        }
        .status-dot-error {
            @apply bg-red-500;
        }
        .status-dot-inactive {
            @apply bg-zinc-600;
        }
    </style>

    @if($isFetching)
        <div class="status-dot status-dot-active animate-pulse"></div>
        <span class="text-xs uppercase tracking-wider text-zinc-400">Fetching...</span>
    @elseif($lastError)
        <div class="status-dot status-dot-error"></div>
        <flux:tooltip :content="$lastError">
            <span class="text-xs uppercase tracking-wider text-red-400">Fetch Error</span>
        </flux:tooltip>
    @elseif($isQueueLocked)
        <div class="status-dot status-dot-paused"></div>
        <span class="text-xs uppercase tracking-wider text-yellow-400">Paused</span>
    @elseif($isActive)
        <div class="status-dot status-dot-active"></div>
        <span class="text-xs text-zinc-400">{{ $lastFetchAt }}</span>
    @else
        <div class="status-dot status-dot-inactive"></div>
        <span class="text-xs uppercase tracking-wider text-zinc-600">Auto-Fetch Off</span>
    @endif
</div>
