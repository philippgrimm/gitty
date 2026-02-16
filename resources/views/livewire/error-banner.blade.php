{{-- Toast Container - Bottom Right --}}
<div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none">
    <div 
        x-data="{ 
            startTimer() {
                setTimeout(() => {
                    $wire.dismiss();
                }, 5000);
            }
        }"
        x-init="$watch('$wire.visible', value => { if (value) startTimer(); })"
        x-show="$wire.visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-x-4 scale-95"
        x-transition:enter-end="opacity-100 translate-x-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-x-0 scale-100"
        x-transition:leave-end="opacity-0 translate-x-4 scale-95"
        class="pointer-events-auto max-w-md w-full bg-white border rounded-lg shadow-lg overflow-hidden border-l-4"
        :class="{
            'border-[#d20f39]/30 border-l-[#d20f39]': '{{ $type }}' === 'error',
            'border-[#fe640b]/30 border-l-[#fe640b]': '{{ $type }}' === 'warning',
            'border-[#084CCF]/30 border-l-[#084CCF]': '{{ $type }}' === 'info'
        }"
        style="display: none;"
    >
        <div class="flex items-start gap-3 p-4 pl-3">
            {{-- Icon --}}
            <div class="flex-shrink-0 mt-0.5">
                @if($type === 'error')
                    <div class="w-5 h-5 rounded-full bg-[#d20f39] flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                @elseif($type === 'warning')
                    <div class="w-5 h-5 rounded-full bg-[#fe640b] flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                @else
                    <div class="w-5 h-5 rounded-full bg-[#084CCF] flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold uppercase tracking-wider mb-1"
                     :class="{
                         'text-[#d20f39]': '{{ $type }}' === 'error',
                         'text-[#fe640b]': '{{ $type }}' === 'warning',
                         'text-[#084CCF]': '{{ $type }}' === 'info'
                     }"
                >
                    @if($type === 'error')
                        Error
                    @elseif($type === 'warning')
                        Warning
                    @else
                        Info
                    @endif
                </div>
                <div class="text-sm text-[#4c4f69] leading-snug font-sans">
                    {{ $message }}
                </div>
            </div>

            {{-- Close Button --}}
            <button 
                wire:click="dismiss"
                class="flex-shrink-0 text-[#9ca0b0] hover:text-[#4c4f69] transition-colors p-1 -mt-1 -mr-1"
                aria-label="Dismiss"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
