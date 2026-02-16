<div 
    wire:poll.30s.visible="refreshSidebar"
    x-data="{ 
        branchesOpen: false, 
        remotesOpen: false, 
        tagsOpen: false, 
        stashesOpen: false,
        showDropModal: false,
        confirmDropIndex: null
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
            <div x-show="stashesOpen" x-collapse class="divide-y divide-[#ccd0da]">
                @forelse($stashes as $stash)
                    <div class="group relative px-4 py-2.5 hover:bg-[#dce0e8] transition-colors">
                        <div class="flex items-center gap-2 mb-1">
                            <flux:badge variant="solid" color="zinc" class="font-mono text-xs">stash@{{{ $stash['index'] }}}</flux:badge>
                            @if($stash['branch'])
                                <flux:badge variant="subtle" color="blue" class="font-mono text-xs">{{ $stash['branch'] }}</flux:badge>
                            @endif
                        </div>
                        <div class="text-xs text-[#6c6f85] truncate">{{ $stash['message'] }}</div>
                        @if($stash['sha'])
                            <div class="text-xs text-[#9ca0b0] font-mono mt-1">{{ substr($stash['sha'], 0, 7) }}</div>
                        @endif
                        <div class="absolute right-0 inset-y-0 flex items-center gap-1 pr-4 pl-2 opacity-0 group-hover:opacity-100 transition-opacity duration-150 bg-[#dce0e8]">
                            <flux:tooltip content="Apply">
                                <flux:button 
                                    wire:click="applyStash({{ $stash['index'] }})"
                                    wire:loading.attr="disabled"
                                    wire:target="applyStash"
                                    variant="ghost" 
                                    size="xs"
                                    square
                                >
                                    <x-phosphor-arrow-counter-clockwise class="w-3.5 h-3.5" />
                                </flux:button>
                            </flux:tooltip>
                            <flux:tooltip content="Pop">
                                <flux:button 
                                    wire:click="popStash({{ $stash['index'] }})"
                                    wire:loading.attr="disabled"
                                    wire:target="popStash"
                                    variant="ghost" 
                                    size="xs"
                                    square
                                >
                                    <x-phosphor-arrow-square-out class="w-3.5 h-3.5" />
                                </flux:button>
                            </flux:tooltip>
                            <flux:tooltip content="Drop">
                                <flux:button 
                                    @click="showDropModal = true; confirmDropIndex = {{ $stash['index'] }}"
                                    variant="ghost" 
                                    size="xs"
                                    square
                                    class="text-[#d20f39] hover:text-[#d20f39]"
                                >
                                    <x-phosphor-trash class="w-3.5 h-3.5" />
                                </flux:button>
                            </flux:tooltip>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-[#9ca0b0] uppercase tracking-wider font-medium">No stashes</div>
                @endforelse
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
</div>
