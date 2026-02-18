{{-- Command Palette Overlay --}}
<div 
    x-show="$wire.isOpen"
    @keydown.escape.prevent.stop="$wire.mode === 'input' ? $wire.cancelInput() : $wire.close()"
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
            class="w-full max-w-xl bg-white dark:bg-[var(--surface-0)] rounded-xl shadow-2xl border border-[var(--border-default)] overflow-hidden mx-4"
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
            @if($mode === 'input')
                {{-- Input Mode Header --}}
                <div class="px-4 py-2.5 border-b border-[var(--border-subtle)]">
                    <div class="flex items-center gap-2">
                        <button wire:click="cancelInput" class="text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors">
                            <x-phosphor-arrow-left class="w-4 h-4" />
                        </button>
                        <span class="text-sm text-[var(--text-primary)] font-medium font-display uppercase tracking-wider">Create Branch</span>
                    </div>
                </div>

                {{-- Input Field --}}
                <div class="px-4 py-3">
                    <input
                        type="text"
                        wire:model="inputValue"
                        wire:keydown.enter.prevent="submitInput"
                        placeholder="Branch name (e.g., feature/my-feature)"
                        class="w-full bg-transparent border border-[var(--border-default)] rounded-lg outline-none text-sm text-[var(--text-primary)] placeholder-[#686C7C] font-mono px-3 py-2 focus:ring-1 focus:ring-[#18206F] focus:border-[#18206F] input-recessed phosphor-glow"
                        x-ref="inputField"
                        x-effect="if($wire.mode === 'input') $nextTick(() => { $refs.inputField?.focus(); const len = $refs.inputField?.value?.length || 0; $refs.inputField?.setSelectionRange(len, len); })"
                    />
                    @if($inputError)
                        <p class="text-[var(--color-red)] text-xs mt-1">{{ $inputError }}</p>
                    @endif
                </div>

                {{-- Footer hint --}}
                <div class="px-4 py-2 border-t border-[var(--border-subtle)] text-[10px] text-[var(--text-tertiary)]">
                    ↵ create · esc back
                </div>
            @else
                {{-- Search Input Area --}}
                <div class="px-4 py-2.5 border-b border-[var(--border-subtle)]">
                    <div class="flex items-center gap-2">
                        <x-phosphor-magnifying-glass-light class="w-4 h-4 text-[var(--text-tertiary)] shrink-0" />
                        <input
                            type="text"
                            wire:model.live.debounce.150ms="query"
                            placeholder="Type a command..."
                            class="w-full bg-transparent border-none outline-none text-sm text-[var(--text-primary)] placeholder-[#686C7C] font-display tracking-wide p-0 focus:ring-0 phosphor-glow"
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
                                @if(!($command['disabled'] ?? false))
                                    wire:click="executeCommand('{{ $command['id'] }}')"
                                @endif
                                class="flex items-center justify-between gap-3 px-4 py-2 transition-colors duration-75
                                    {{ ($command['disabled'] ?? false) ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)]' }}"
                                x-bind:style="activeIndex === {{ $index }} && !{{ ($command['disabled'] ?? false) ? 'true' : 'false' }} ? 'background-color: rgba(24, 32, 111, 0.08)' : ''"
                            >
                                <div class="flex items-center gap-3">
                                    <x-dynamic-component :component="$command['icon']" class="w-4 h-4 text-[var(--text-tertiary)] shrink-0" />
                                    <span class="text-[13px] text-[var(--text-primary)]">{{ $command['label'] }}</span>
                                </div>
                                @if($command['shortcut'])
                                    <kbd class="text-[10px] text-[var(--text-secondary)] bg-[var(--surface-0)] border border-[var(--border-default)] rounded px-1.5 py-0.5 font-mono shrink-0">
                                        {{ $command['shortcut'] }}
                                    </kbd>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Footer hints --}}
                    <div class="px-4 py-2 border-t border-[var(--border-subtle)] text-[10px] text-[var(--text-tertiary)]">
                        ↑↓ navigate · ↵ select · esc close
                    </div>
                @else
                    <div class="py-8 text-center text-sm text-[var(--text-tertiary)]">
                        No commands found
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
