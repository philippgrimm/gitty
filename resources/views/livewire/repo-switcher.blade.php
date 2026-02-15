<div class="flex items-center gap-3 font-mono">
    @if($error)
        <div class="absolute top-16 left-1/2 transform -translate-x-1/2 z-50 bg-[#d20f39]/10 border border-[#d20f39]/30 text-[#d20f39] px-6 py-3 text-xs uppercase tracking-wider font-semibold shadow-xl">
            {{ $error }}
        </div>
    @endif

    <flux:dropdown position="bottom-start">
        <flux:button 
            variant="subtle" 
            size="xs"
            class="flex items-center gap-2 px-2.5 py-1 !bg-[#eff1f5] border border-[#ccd0da] hover:border-[#bcc0cc] transition-colors text-xs rounded-lg"
        >
            <x-phosphor-folder-light class="w-3.5 h-3.5 text-[#6c6f85] shrink-0" />
            @if($currentRepoName)
                <span class="font-semibold text-[#4c4f69]">{{ $currentRepoName }}</span>
            @else
                <span class="text-[#9ca0b0]">No repository open</span>
            @endif
            <svg class="w-3 h-3 text-[#6c6f85]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </flux:button>

        <flux:menu class="w-80 max-h-[500px] overflow-hidden !p-0">
            <div
                class="flex flex-col h-full"
                x-data="{
                    activeIndex: -1,
                    items: [],
                    init() {
                        this.updateItems();
                    },
                    updateItems() {
                        this.items = [...this.$el.querySelectorAll('[data-repo-item]')];
                        this.activeIndex = -1;
                    },
                    navigate(direction) {
                        if (this.items.length === 0) return;
                        if (direction === 'down') {
                            this.activeIndex = this.activeIndex < this.items.length - 1 ? this.activeIndex + 1 : 0;
                        } else {
                            this.activeIndex = this.activeIndex > 0 ? this.activeIndex - 1 : this.items.length - 1;
                        }
                        this.items[this.activeIndex]?.scrollIntoView({ block: 'nearest' });
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
                @if(count($recentRepos) > 0)
                    <div class="flex-1 overflow-y-auto">
                        @foreach($recentRepos as $repo)
                            <div
                                class="group flex items-center justify-between px-3 py-1.5 transition-colors cursor-pointer"
                                :class="activeIndex === {{ $loop->index }} ? 'bg-[var(--surface-2)]' : 'hover:bg-[var(--surface-2)]'"
                                data-repo-item
                                wire:click="switchRepo({{ $repo['id'] }})"
                                x-on:click="$el.closest('[popover]')?.hidePopover()"
                            >
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <div class="w-4 flex items-center justify-center shrink-0">
                                        @if($currentRepoPath === $repo['path'])
                                            <div class="w-1.5 h-1.5 rounded-full bg-[var(--accent)]"></div>
                                        @endif
                                    </div>
                                    <span class="text-sm truncate {{ $currentRepoPath === $repo['path'] ? 'font-semibold text-[var(--text-primary)]' : 'text-[var(--text-secondary)]' }}">
                                        {{ $repo['name'] }}
                                    </span>
                                </div>

                                <button
                                    wire:click.stop="removeRecentRepo({{ $repo['id'] }})"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity text-[var(--text-tertiary)] hover:text-[var(--color-red)] p-0.5 shrink-0"
                                >
                                    <x-phosphor-trash-light class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    @if(!$currentRepoName)
                        <div class="px-4 py-8 text-center animate-fade-in">
                            <div class="w-12 h-12 mx-auto mb-2 opacity-60">{!! file_get_contents(resource_path('svg/empty-states/no-repo.svg')) !!}</div>
                            <div class="text-xs uppercase tracking-wider text-[var(--text-tertiary)]">No repositories yet</div>
                        </div>
                    @endif
                @endif

                {{-- Open Repository button --}}
                <div class="border-t border-[var(--border-subtle)] p-2 sticky bottom-0 bg-white">
                    <button
                        wire:click="openFolderDialog"
                        type="button"
                        class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs uppercase tracking-wider text-[var(--text-secondary)] hover:bg-[var(--surface-2)] transition-colors rounded"
                    >
                        <x-phosphor-folder-open-light class="w-3.5 h-3.5 shrink-0" />
                        <span>Open Repository</span>
                    </button>
                </div>
            </div>
        </flux:menu>
    </flux:dropdown>
</div>
