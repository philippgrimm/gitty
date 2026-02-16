{{-- Command Palette Overlay --}}
<div 
    x-show="$wire.isOpen"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="fixed inset-0 z-50"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div 
        class="absolute inset-0 bg-black/50"
        @click="$wire.close()"
    ></div>

    {{-- Card Container --}}
    <div class="relative flex items-start justify-center pt-[20vh]">
        <div 
            class="w-full max-w-xl bg-white rounded-xl shadow-2xl border border-[#ccd0da] overflow-hidden mx-4"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
        >
            {{-- Search Input Area --}}
            <div class="px-4 py-2.5 border-b border-[#dce0e8]">
                <div class="flex items-center gap-2">
                    <x-phosphor-magnifying-glass-light class="w-4 h-4 text-[#8c8fa1] shrink-0" />
                    <input
                        type="text"
                        wire:model.live.debounce.150ms="query"
                        placeholder="Type a command..."
                        class="w-full bg-transparent border-none outline-none text-sm text-[#4c4f69] placeholder-[#8c8fa1] font-mono p-0 focus:ring-0"
                        x-ref="searchInput"
                        x-effect="if($wire.isOpen) $nextTick(() => $refs.searchInput?.focus())"
                    />
                </div>
            </div>

            {{-- Command List Placeholder --}}
            <div class="py-8 text-center text-sm text-[#8c8fa1]">
                {{-- Command list will be populated in next task --}}
            </div>
        </div>
    </div>
</div>
