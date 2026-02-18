<div 
    wire:poll.30s.visible="checkAndFetch"
    class="flex items-center gap-2 px-3 py-2 bg-[var(--surface-0)] text-[var(--text-primary)] font-mono border-l border-[var(--border-default)]"
    x-data="{ showTooltip: false }"
>
    <style>
        .status-dot {
            @apply w-2 h-2 rounded-full;
        }
        .status-dot-active {
            @apply bg-[var(--color-yellow)];
        }
        .status-dot-paused {
            @apply bg-[var(--color-yellow)];
        }
        .status-dot-error {
            @apply bg-[var(--color-red)];
        }
        .status-dot-inactive {
            @apply bg-[#4A4E5E];
        }
    </style>

    @if($isFetching)
        <div class="status-dot status-dot-active animate-pulse"></div>
        <span class="text-xs uppercase tracking-wider text-[var(--text-tertiary)]">Fetching...</span>
    @elseif($lastError)
        <div class="status-dot status-dot-error"></div>
        <flux:tooltip :content="$lastError">
            <span class="text-xs uppercase tracking-wider text-[var(--color-red)]">Fetch Error</span>
        </flux:tooltip>
    @elseif($isQueueLocked)
        <div class="status-dot status-dot-paused"></div>
        <span class="text-xs uppercase tracking-wider text-[var(--color-yellow)]">Paused</span>
    @elseif($isActive)
        <div class="status-dot status-dot-active"></div>
        <span class="text-xs text-[var(--text-tertiary)]">{{ $lastFetchAt }}</span>
    @else
        <div class="status-dot status-dot-inactive"></div>
        <span class="text-xs uppercase tracking-wider text-[var(--text-tertiary)]">Auto-Fetch Off</span>
    @endif
</div>
