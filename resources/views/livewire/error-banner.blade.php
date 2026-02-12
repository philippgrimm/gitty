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
        'bg-red-950 border-red-800 text-red-200': '{{ $type }}' === 'error',
        'bg-orange-950 border-orange-800 text-orange-200': '{{ $type }}' === 'warning',
        'bg-blue-950 border-blue-800 text-blue-200': '{{ $type }}' === 'info'
    }"
    style="display: none;"
>
    <div class="flex items-center gap-3">
        <span class="text-lg font-bold uppercase tracking-widest">
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
        class="text-xl hover:opacity-70 transition-opacity"
        aria-label="Dismiss"
    >
        ×
    </button>
</div>
