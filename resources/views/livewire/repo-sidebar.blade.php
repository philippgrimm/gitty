<div 
    wire:poll.10s.visible="refreshSidebar"
    x-data="{ 
        branchesOpen: true, 
        remotesOpen: false, 
        tagsOpen: false, 
        stashesOpen: false 
    }"
    class="h-full flex flex-col bg-zinc-950 text-zinc-100 font-mono overflow-hidden"
>
    <div class="flex-1 overflow-y-auto">
        <div class="border-b-2 border-zinc-800">
            <button 
                @click="branchesOpen = !branchesOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-zinc-900 transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">Branches</div>
                    <flux:badge variant="solid" color="zinc" class="font-mono text-xs">{{ count($branches) }}</flux:badge>
                </div>
                <div class="text-zinc-400 transition-transform" :class="{ 'rotate-90': branchesOpen }">▶</div>
            </button>
            <div x-show="branchesOpen" x-collapse class="divide-y divide-zinc-800">
                @forelse($branches as $branch)
                    <button 
                        wire:click="switchBranch('{{ $branch['name'] }}')"
                        class="w-full px-4 py-2.5 hover:bg-zinc-900 transition-colors flex items-center justify-between gap-3 group"
                    >
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            @if($branch['isCurrent'])
                                <div class="text-green-400 text-xs">✓</div>
                            @else
                                <div class="w-3"></div>
                            @endif
                            <flux:tooltip :content="$branch['name']">
                                <div class="text-sm truncate {{ $branch['isCurrent'] ? 'text-green-400 font-bold' : 'text-zinc-200' }} group-hover:text-zinc-100 transition-colors">
                                    {{ $branch['name'] }}
                                </div>
                            </flux:tooltip>
                        </div>
                        @if($branch['lastCommitSha'])
                            <div class="text-xs text-zinc-400 font-mono">{{ substr($branch['lastCommitSha'], 0, 7) }}</div>
                        @endif
                    </button>
                @empty
                    <div class="px-4 py-3 text-xs text-zinc-400 uppercase tracking-wider">No branches</div>
                @endforelse
            </div>
        </div>

        <div class="border-b-2 border-zinc-800">
            <button 
                @click="remotesOpen = !remotesOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-zinc-900 transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">Remotes</div>
                    <flux:badge variant="solid" color="zinc" class="font-mono text-xs">{{ count($remotes) }}</flux:badge>
                </div>
                <div class="text-zinc-400 transition-transform" :class="{ 'rotate-90': remotesOpen }">▶</div>
            </button>
            <div x-show="remotesOpen" x-collapse class="divide-y divide-zinc-800">
                @forelse($remotes as $remote)
                    <div class="px-4 py-2.5">
                        <div class="text-sm text-zinc-200 font-bold mb-1">{{ $remote['name'] }}</div>
                        <flux:tooltip :content="$remote['fetchUrl']">
                            <div class="text-xs text-zinc-400 truncate">{{ $remote['fetchUrl'] }}</div>
                        </flux:tooltip>
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-zinc-400 uppercase tracking-wider">No remotes</div>
                @endforelse
            </div>
        </div>

        <div class="border-b-2 border-zinc-800">
            <button 
                @click="tagsOpen = !tagsOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-zinc-900 transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">Tags</div>
                    <flux:badge variant="solid" color="zinc" class="font-mono text-xs">{{ count($tags) }}</flux:badge>
                </div>
                <div class="text-zinc-400 transition-transform" :class="{ 'rotate-90': tagsOpen }">▶</div>
            </button>
            <div x-show="tagsOpen" x-collapse class="divide-y divide-zinc-800">
                @forelse($tags as $tag)
                    <div class="px-4 py-2.5 hover:bg-zinc-900 transition-colors flex items-center justify-between gap-3">
                        <flux:tooltip :content="$tag['name']">
                            <div class="text-sm truncate text-zinc-200">{{ $tag['name'] }}</div>
                        </flux:tooltip>
                        @if($tag['sha'])
                            <div class="text-xs text-zinc-400 font-mono">{{ substr($tag['sha'], 0, 7) }}</div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-zinc-400 uppercase tracking-wider">No tags</div>
                @endforelse
            </div>
        </div>

        <div class="border-b-2 border-zinc-800">
            <button 
                @click="stashesOpen = !stashesOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-zinc-900 transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">Stashes</div>
                    <flux:badge variant="solid" color="zinc" class="font-mono text-xs">{{ count($stashes) }}</flux:badge>
                </div>
                <div class="text-zinc-400 transition-transform" :class="{ 'rotate-90': stashesOpen }">▶</div>
            </button>
            <div x-show="stashesOpen" x-collapse class="divide-y divide-zinc-800">
                @forelse($stashes as $stash)
                    <div class="px-4 py-2.5 hover:bg-zinc-900 transition-colors">
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
                    <div class="px-4 py-3 text-xs text-zinc-400 uppercase tracking-wider">No stashes</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
