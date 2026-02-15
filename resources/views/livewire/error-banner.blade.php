<div 
    x-data="{ 
        autoHide: @entangle('persistent').live === false,
        startTimer() {
            if (!this.autoHide) return;
            setTimeout(() => {
                $wire.dismiss();
            }, 10000);
        }
    }"
    x-init="$watch('$wire.visible', value => { if (value && autoHide) startTimer(); })"
    x-show="$wire.visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-6 py-4 font-mono text-sm border-b"
    :class="{
        'bg-[#d20f39]/10 border-[#d20f39]/30 text-[#d20f39]': '{{ $type }}' === 'error',
        'bg-[#fe640b]/10 border-[#fe640b]/30 text-[#fe640b]': '{{ $type }}' === 'warning',
        'bg-[#084CCF]/10 border-[#084CCF]/30 text-[#084CCF]': '{{ $type }}' === 'info'
    }"
    style="display: none;"
>
    <div class="flex items-center gap-3">
        <span class="text-lg font-semibold uppercase tracking-wider">
            @if($type === 'error')
                ERROR
            @elseif($type === 'warning')
                WARNING
            @else
                INFO
            @endif
        </span>
        <span>{{ $message }}</span>
    </div>
    
    <button 
        wire:click="dismiss"
        class="text-lg font-semibold px-2 py-1 rounded hover:bg-black/5 transition-colors"
        aria-label="Dismiss"
    >
        ×
    </button>
</div>
