<div 
    wire:poll.10s.visible="refreshSidebar"
    x-data="{ 
        branchesOpen: false, 
        remotesOpen: false, 
        tagsOpen: false, 
        stashesOpen: false 
    }"
    class="h-full flex flex-col bg-zinc-950 text-zinc-100 font-mono overflow-hidden"
>
    <div class="flex-1 overflow-y-auto">
        <div class="border-b border-zinc-800">
            <button 
                @click="branchesOpen = !branchesOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-zinc-800/30 transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-wider font-medium text-zinc-400">Branches</div>
                    <flux:badge variant="solid" color="zinc" class="font-mono text-xs">{{ count($branches) }}</flux:badge>
                </div>
                <div class="text-zinc-500 transition-transform duration-150" :class="{ 'rotate-90': branchesOpen }">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </button>
            <div x-show="branchesOpen" x-collapse class="divide-y divide-zinc-800">
                @forelse($branches as $branch)
                    <button 
                        wire:click="switchBranch('{{ $branch['name'] }}')"
                        class="w-full px-4 py-2.5 hover:bg-zinc-800/30 transition-colors flex items-center justify-between gap-3 group"
                    >
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            @if($branch['isCurrent'])
                                <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                            @else
                                <div class="w-2"></div>
                            @endif
                            <flux:tooltip :content="$branch['name']">
                                <div class="text-sm truncate {{ $branch['isCurrent'] ? 'text-amber-400 font-semibold' : 'text-zinc-200' }} group-hover:text-zinc-100 transition-colors">
                                    {{ $branch['name'] }}
                                </div>
                            </flux:tooltip>
                        </div>
                        @if($branch['lastCommitSha'])
                            <div class="text-xs text-zinc-400 font-mono">{{ substr($branch['lastCommitSha'], 0, 7) }}</div>
                        @endif
                    </button>
                @empty
                    <div class="px-4 py-3 text-xs text-zinc-400 uppercase tracking-wider font-medium">No branches</div>
                @endforelse
            </div>
        </div>

        <div class="border-b border-zinc-800">
            <button 
                @click="remotesOpen = !remotesOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-zinc-800/30 transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-wider font-medium text-zinc-400">Remotes</div>
                    <flux:badge variant="solid" color="zinc" class="font-mono text-xs">{{ count($remotes) }}</flux:badge>
                </div>
                <div class="text-zinc-500 transition-transform duration-150" :class="{ 'rotate-90': remotesOpen }">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </button>
            <div x-show="remotesOpen" x-collapse class="divide-y divide-zinc-800">
                @forelse($remotes as $remote)
                    <div class="px-4 py-2.5">
                        <div class="text-sm text-zinc-200 font-semibold mb-1">{{ $remote['name'] }}</div>
                        <flux:tooltip :content="$remote['fetchUrl']">
                            <div class="text-xs text-zinc-400 truncate">{{ $remote['fetchUrl'] }}</div>
                        </flux:tooltip>
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-zinc-400 uppercase tracking-wider font-medium">No remotes</div>
                @endforelse
            </div>
        </div>

        <div class="border-b border-zinc-800">
            <button 
                @click="tagsOpen = !tagsOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-zinc-800/30 transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-wider font-medium text-zinc-400">Tags</div>
                    <flux:badge variant="solid" color="zinc" class="font-mono text-xs">{{ count($tags) }}</flux:badge>
                </div>
                <div class="text-zinc-500 transition-transform duration-150" :class="{ 'rotate-90': tagsOpen }">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </button>
            <div x-show="tagsOpen" x-collapse class="divide-y divide-zinc-800">
                @forelse($tags as $tag)
                    <div class="px-4 py-2.5 hover:bg-zinc-800/30 transition-colors flex items-center justify-between gap-3">
                        <flux:tooltip :content="$tag['name']">
                            <div class="text-sm truncate text-zinc-200">{{ $tag['name'] }}</div>
                        </flux:tooltip>
                        @if($tag['sha'])
                            <div class="text-xs text-zinc-400 font-mono">{{ substr($tag['sha'], 0, 7) }}</div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-zinc-400 uppercase tracking-wider font-medium">No tags</div>
                @endforelse
            </div>
        </div>

        <div class="border-b border-zinc-800">
            <button 
                @click="stashesOpen = !stashesOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-zinc-800/30 transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-wider font-medium text-zinc-400">Stashes</div>
                    <flux:badge variant="solid" color="zinc" class="font-mono text-xs">{{ count($stashes) }}</flux:badge>
                </div>
                <div class="text-zinc-500 transition-transform duration-150" :class="{ 'rotate-90': stashesOpen }">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </button>
            <div x-show="stashesOpen" x-collapse class="divide-y divide-zinc-800">
                @forelse($stashes as $stash)
                    <div class="px-4 py-2.5 hover:bg-zinc-800/30 transition-colors">
                        <div class="flex items-center gap-2 mb-1">
                            <flux:badge variant="solid" color="zinc" class="font-mono text-xs">stash@{{{ $stash['index'] }}}</flux:badge>
                            @if($stash['branch'])
                                <flux:badge variant="subtle" color="blue" class="font-mono text-xs">{{ $stash['branch'] }}</flux:badge>
                            @endif
                        </div>
                        <div class="text-xs text-zinc-300 truncate">{{ $stash['message'] }}</div>
                        @if($stash['sha'])
                            <div class="text-xs text-zinc-400 font-mono mt-1">{{ substr($stash['sha'], 0, 7) }}</div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-zinc-400 uppercase tracking-wider font-medium">No stashes</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
