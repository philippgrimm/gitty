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
            x-data="{
                activeIndex: -1,
                items: [],
                init() {
                    this.updateItems();
                    $watch('$wire.query', () => $nextTick(() => this.updateItems()));
                },
                updateItems() {
                    this.items = Array.from(this.$el.querySelectorAll('[data-command-item]'));
                    this.activeIndex = -1;
                },
                navigate(direction) {
                    if (this.items.length === 0) return;
                    
                    if (direction === 'down') {
                        this.activeIndex = (this.activeIndex + 1) % this.items.length;
                    } else {
                        this.activeIndex = this.activeIndex <= 0 ? this.items.length - 1 : this.activeIndex - 1;
                    }
                    
                    this.items[this.activeIndex]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                },
                selectActive() {
                    if (this.activeIndex >= 0 && this.items[this.activeIndex]) {
                        this.items[this.activeIndex].click();
                    }
                }
            }"
            @keydown.arrow-down.prevent="navigate('down')"
            @keydown.arrow-up.prevent="navigate('up')"
            @keydown.enter.prevent="selectActive()"
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

            {{-- Command List --}}
            @if(count($this->filteredCommands) > 0)
                <div class="max-h-80 overflow-y-auto">
                    @foreach($this->filteredCommands as $index => $command)
                        <div
                            data-command-item
                            wire:click="executeCommand('{{ $command['id'] }}')"
                            class="flex items-center justify-between gap-3 px-4 py-2 cursor-pointer hover:bg-[#eff1f5] transition-colors duration-75"
                            :class="{ 'bg-[#eff1f5]': activeIndex === {{ $index }} }"
                        >
                            <div class="flex items-center gap-3">
                                <x-dynamic-component :component="$command['icon']" class="w-4 h-4 text-[#9ca0b0] shrink-0" />
                                <span class="text-[13px] text-[#4c4f69]">{{ $command['label'] }}</span>
                            </div>
                            @if($command['shortcut'])
                                <kbd class="text-[10px] text-[#6c6f85] bg-[#eff1f5] border border-[#ccd0da] rounded px-1.5 py-0.5 font-mono">
                                    {{ $command['shortcut'] }}
                                </kbd>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-sm text-[#8c8fa1]">
                    No commands found
                </div>
            @endif
        </div>
    </div>
</div>
