<div 
    wire:poll.30s.visible="refreshSidebar"
    x-data="{ 
        branchesOpen: false, 
        remotesOpen: false, 
        tagsOpen: false, 
        stashesOpen: false 
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
                    <div class="px-4 py-2.5 hover:bg-[#dce0e8] transition-colors">
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
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-[#9ca0b0] uppercase tracking-wider font-medium">No stashes</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
