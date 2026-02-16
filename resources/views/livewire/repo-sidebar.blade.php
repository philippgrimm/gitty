<div 
    wire:poll.30s.visible="refreshSidebar"
    x-data="{ 
        branchesOpen: false, 
        remotesOpen: false, 
        tagsOpen: false, 
        stashesOpen: false,
        showDropModal: false,
        confirmDropIndex: null,
        stashMenu: { show: false, x: 0, y: 0, index: null },
        
        openStashMenu(index, event) {
            this.stashMenu = { 
                show: true, 
                x: event.clientX, 
                y: event.clientY, 
                index: index 
            };
        },
        
        hideStashMenu() { 
            this.stashMenu.show = false; 
        }
    }"
    class="h-full flex flex-col bg-[#eff1f5] text-[#4c4f69] font-mono overflow-hidden"
>
    <div class="flex-1 overflow-y-auto">
        <div class="border-b border-[#ccd0da]">
            <button 
                @click="remotesOpen = !remotesOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-[#dce0e8] transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-wider font-medium text-[#9ca0b0]">Remotes</div>
                    <span class="text-xs text-[#9ca0b0] font-mono">{{ count($remotes) }}</span>
                </div>
                <div class="text-[#8c8fa1] transition-transform duration-150" :class="{ 'rotate-90': remotesOpen }">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </button>
            <div x-show="remotesOpen" x-collapse class="divide-y divide-[#ccd0da]">
                @forelse($remotes as $remote)
                    <div class="px-4 py-2.5">
                        <div class="text-sm text-[#5c5f77] font-semibold mb-1">{{ $remote['name'] }}</div>
                        <flux:tooltip :content="$remote['fetchUrl']">
                            <div class="text-xs text-[#9ca0b0] truncate">{{ $remote['fetchUrl'] }}</div>
                        </flux:tooltip>
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-[#9ca0b0] uppercase tracking-wider font-medium">No remotes</div>
                @endforelse
            </div>
        </div>

        <div class="border-b border-[#ccd0da]">
            <button 
                @click="tagsOpen = !tagsOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-[#dce0e8] transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-wider font-medium text-[#9ca0b0]">Tags</div>
                    <span class="text-xs text-[#9ca0b0] font-mono">{{ count($tags) }}</span>
                </div>
                <div class="text-[#8c8fa1] transition-transform duration-150" :class="{ 'rotate-90': tagsOpen }">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </button>
            <div x-show="tagsOpen" x-collapse class="divide-y divide-[#ccd0da]">
                @forelse($tags as $tag)
                    <div class="px-4 py-2.5 hover:bg-[#dce0e8] transition-colors flex items-center justify-between gap-3">
                        <flux:tooltip :content="$tag['name']">
                            <div class="text-sm truncate text-[#5c5f77]">{{ $tag['name'] }}</div>
                        </flux:tooltip>
                        @if($tag['sha'])
                            <div class="text-xs text-[#9ca0b0] font-mono">{{ substr($tag['sha'], 0, 7) }}</div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-[#9ca0b0] uppercase tracking-wider font-medium">No tags</div>
                @endforelse
            </div>
        </div>

        <div class="border-b border-[#ccd0da]">
            <button 
                @click="stashesOpen = !stashesOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-[#dce0e8] transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-wider font-medium text-[#9ca0b0]">Stashes</div>
                    <span class="text-xs text-[#9ca0b0] font-mono">{{ count($stashes) }}</span>
                </div>
                <div class="text-[#8c8fa1] transition-transform duration-150" :class="{ 'rotate-90': stashesOpen }">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </button>
            <div x-show="stashesOpen" x-collapse>
                @forelse($stashes as $stash)
                    <div 
                        @contextmenu.prevent="openStashMenu({{ $stash['index'] }}, $event)"
                        class="px-4 py-2 hover:bg-[#dce0e8] transition-colors flex items-center gap-2"
                    >
                        {{-- Stash reference --}}
                        <div class="text-xs text-[#9ca0b0] font-mono shrink-0">{{ 'stash@{' . $stash['index'] . '}' }}</div>
                        
                        {{-- Message (truncated, fills remaining space) --}}
                        <div class="text-sm text-[#5c5f77] truncate flex-1 min-w-0">{{ $stash['message'] }}</div>
                        
                        {{-- 3-dot menu button (always visible, subtle) --}}
                        <button 
                            @click.stop="openStashMenu({{ $stash['index'] }}, $event)"
                            class="shrink-0 p-0.5 rounded text-[#9ca0b0] hover:text-[#6c6f85] hover:bg-[#ccd0da] transition-colors"
                        >
                            <x-phosphor-dots-three class="w-4 h-4" />
                        </button>
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-[#9ca0b0] uppercase tracking-wider font-medium">No stashes</div>
                @endforelse

                {{-- Stash context menu --}}
                <div
                    x-show="stashMenu.show"
                    x-cloak
                    @click.outside="hideStashMenu()"
                    @keydown.escape.window="hideStashMenu()"
                    @scroll.window="hideStashMenu()"
                    :style="`position: fixed; left: ${stashMenu.x}px; top: ${stashMenu.y}px; z-index: 50;`"
                    class="bg-white border border-[#ccd0da] rounded-lg shadow-lg py-1 min-w-[140px] font-mono text-sm"
                >
                    <button 
                        @click="$wire.applyStash(stashMenu.index); hideStashMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#4c4f69] flex items-center gap-2"
                    >
                        <x-phosphor-arrow-counter-clockwise class="w-3.5 h-3.5" />
                        Apply
                    </button>
                    <button 
                        @click="$wire.popStash(stashMenu.index); hideStashMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#4c4f69] flex items-center gap-2"
                    >
                        <x-phosphor-arrow-square-out class="w-3.5 h-3.5" />
                        Pop
                    </button>
                    <div class="border-t border-[#dce0e8] my-1"></div>
                    <button 
                        @click="confirmDropIndex = stashMenu.index; showDropModal = true; hideStashMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#d20f39] flex items-center gap-2"
                    >
                        <x-phosphor-trash class="w-3.5 h-3.5" />
                        Drop
                    </button>
                </div>
            </div>
        </div>
    </div>

    <flux:modal x-model="showDropModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Drop Stash?</flux:heading>
            <flux:subheading class="font-mono">
                This will permanently delete the stash. This action cannot be undone.
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" @click="showDropModal = false">Cancel</flux:button>
            <flux:button 
                variant="danger" 
                @click="
                    $wire.dropStash(confirmDropIndex);
                    showDropModal = false;
                    confirmDropIndex = null;
                "
            >
                Drop
            </flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showAutoStashModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Stash & Switch?</flux:heading>
            <flux:subheading class="font-mono">
                You have uncommitted changes that conflict with <span class="text-[var(--text-primary)] font-bold">{{ $autoStashTargetBranch }}</span>. Stash them and switch?
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="cancelAutoStash">Cancel</flux:button>
            <flux:button 
                variant="primary" 
                wire:click="confirmAutoStash"
                class="uppercase tracking-wider"
            >
                Stash & Switch
            </flux:button>
        </div>
    </flux:modal>
</div>
