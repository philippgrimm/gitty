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
        class="pointer-events-auto max-w-md w-full bg-white dark:bg-[var(--surface-0)] border rounded-lg shadow-lg overflow-hidden border-l-4"
        :class="{
            'border-[#D91440]/30 border-l-[#D91440]': '{{ $type }}' === 'error',
            'border-[#E05800]/30 border-l-[#E05800]': '{{ $type }}' === 'warning',
            'border-[#4040B0]/30 border-l-[#4040B0]': '{{ $type }}' === 'info',
            'border-[#1E8C0A]/30 border-l-[#1E8C0A]': '{{ $type }}' === 'success'
        }"
        style="display: none;"
    >
        <div class="flex items-start gap-3 p-4 pl-3">
            {{-- Icon --}}
            <div class="flex-shrink-0 mt-0.5">
                @if($type === 'error')
                    <div class="w-5 h-5 rounded-full bg-[var(--color-red)] flex items-center justify-center">
                        <x-pixelarticons-close class="w-3 h-3 text-white" />
                    </div>
                @elseif($type === 'warning')
                    <div class="w-5 h-5 rounded-full bg-[var(--color-peach)] flex items-center justify-center">
                        <x-pixelarticons-alert class="w-3 h-3 text-white" />
                    </div>
                @elseif($type === 'success')
                    <div class="w-5 h-5 rounded-full bg-[var(--color-green)] flex items-center justify-center">
                        <x-pixelarticons-check class="w-3 h-3 text-white" />
                    </div>
                 @else
                    <div class="w-5 h-5 rounded-full bg-[#4040B0] flex items-center justify-center">
                        <x-pixelarticons-info-box class="w-3 h-3 text-white" />
                    </div>
                @endif
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold uppercase tracking-wider mb-1"
                     :class="{
                         'text-[var(--color-red)]': '{{ $type }}' === 'error',
                         'text-[var(--color-peach)]': '{{ $type }}' === 'warning',
                         'text-[#4040B0]': '{{ $type }}' === 'info',
                         'text-[var(--color-green)]': '{{ $type }}' === 'success'
                     }"
                >
                    @if($type === 'error')
                        Error
                    @elseif($type === 'warning')
                        Warning
                    @elseif($type === 'success')
                        Success
                    @else
                        Info
                    @endif
                </div>
                <div class="text-sm text-[var(--text-primary)] leading-snug font-sans">
                    {{ $message }}
                </div>
            </div>

            {{-- Close Button --}}
            <button 
                wire:click="dismiss"
                class="flex-shrink-0 text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors p-1 -mt-1 -mr-1"
                aria-label="Dismiss"
            >
                <x-pixelarticons-close class="w-4 h-4" />
            </button>
        </div>
    </div>
</div>
