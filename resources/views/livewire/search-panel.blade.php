{{-- Search Panel Overlay --}}
<div 
    x-show="$wire.isOpen"
    @keydown.escape.prevent.stop="$wire.close()"
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
            class="w-full max-w-2xl bg-[#F2EFE9] rounded-xl shadow-2xl border border-[#C8C3B8] overflow-hidden mx-4"
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
                    $watch('$wire.results', () => $nextTick(() => this.updateItems()));
                },
                updateItems() {
                    this.items = Array.from(this.$el.querySelectorAll('[data-result-item]'));
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
            <div class="px-4 py-2.5 border-b border-[#D4CFC6]">
                <div class="flex items-center gap-2">
                    <x-phosphor-magnifying-glass-light class="w-4 h-4 text-[#686C7C] shrink-0" />
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="query"
                        placeholder="Search commits, content, or files..."
                        class="w-full bg-transparent border-none outline-none text-sm text-[#2C3040] placeholder-[#686C7C] font-display tracking-wide p-0 focus:ring-0 phosphor-glow"
                        x-ref="searchInput"
                        x-effect="if($wire.isOpen) $nextTick(() => $refs.searchInput?.focus())"
                    />
                </div>
            </div>

            {{-- Scope Tabs --}}
            <div class="px-4 py-2 border-b border-[#D4CFC6] flex gap-2">
                <button
                    wire:click="setScope('commits')"
                    class="px-3 py-1 text-xs font-medium font-display uppercase tracking-wider rounded transition-colors {{ $scope === 'commits' ? 'bg-[#18206F] text-white' : 'text-[#4A4E5E] hover:bg-[#E8E5DF]' }}"
                >
                    Commits
                </button>
                <button
                    wire:click="setScope('content')"
                    class="px-3 py-1 text-xs font-medium font-display uppercase tracking-wider rounded transition-colors {{ $scope === 'content' ? 'bg-[#18206F] text-white' : 'text-[#4A4E5E] hover:bg-[#E8E5DF]' }}"
                >
                    Content
                </button>
                <button
                    wire:click="setScope('files')"
                    class="px-3 py-1 text-xs font-medium font-display uppercase tracking-wider rounded transition-colors {{ $scope === 'files' ? 'bg-[#18206F] text-white' : 'text-[#4A4E5E] hover:bg-[#E8E5DF]' }}"
                >
                    Files
                </button>
            </div>

            {{-- Results List --}}
            @if(strlen($query) < 3)
                <div class="py-8 text-center text-sm text-[#686C7C]">
                    Type at least 3 characters to search
                </div>
            @elseif(count($results) > 0)
                <div class="max-h-96 overflow-y-auto">
                    @if($scope === 'files')
                        @foreach($results as $index => $result)
                            <div
                                data-result-item
                                wire:click="selectResult('{{ $result['path'] }}')"
                                class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-[#E8E5DF] transition-colors duration-75"
                                x-bind:style="activeIndex === {{ $index }} ? 'background-color: rgba(24, 32, 111, 0.08)' : ''"
                            >
                                <x-phosphor-file-text class="w-4 h-4 text-[#4A4E5E] shrink-0" />
                                <span class="text-[13px] text-[#2C3040] font-mono">{{ $result['path'] }}</span>
                            </div>
                        @endforeach
                    @else
                        @foreach($results as $index => $result)
                            <div
                                data-result-item
                                wire:click="selectResult('{{ $result['sha'] }}')"
                                class="flex items-start gap-3 px-4 py-2.5 cursor-pointer hover:bg-[#E8E5DF] transition-colors duration-75"
                                x-bind:style="activeIndex === {{ $index }} ? 'background-color: rgba(24, 32, 111, 0.08)' : ''"
                            >
                                <x-phosphor-git-commit class="w-4 h-4 text-[#4A4E5E] shrink-0 mt-0.5" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] text-[#686C7C] font-mono">{{ $result['shortSha'] }}</span>
                                        <span class="text-[13px] text-[#2C3040] truncate">{{ $result['message'] }}</span>
                                    </div>
                                    <div class="text-[11px] text-[#686C7C] mt-0.5">
                                        {{ $result['author'] }} · {{ $result['date'] }}
                                        @if($scope === 'content')
                                            <span class="text-[#4A4E5E]"> · contains match</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Footer hints --}}
                <div class="px-4 py-2 border-t border-[#D4CFC6] text-[10px] text-[#686C7C]">
                    ↑↓ navigate · ↵ select · esc close
                </div>
            @else
                <div class="py-8 text-center text-sm text-[#686C7C]">
                    No results found
                </div>
            @endif
        </div>
    </div>
</div>
