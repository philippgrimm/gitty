<div class="flex items-center gap-3 font-mono">
    @if($error)
        <div class="absolute top-16 left-1/2 transform -translate-x-1/2 z-50 bg-red-950 border-2 border-red-800 text-red-200 px-6 py-3 text-xs uppercase tracking-wider font-bold shadow-xl">
            {{ $error }}
        </div>
    @endif

    <flux:dropdown position="bottom-start">
        <flux:button 
            variant="subtle" 
            size="sm" 
            icon:trailing="chevron-down"
            class="uppercase tracking-wider text-xs"
        >
            @if($currentRepoName)
                <span class="font-bold">{{ $currentRepoName }}</span>
            @else
                <span class="text-zinc-500">No repository open</span>
            @endif
        </flux:button>

        <flux:menu class="w-80">
            @if($currentRepoName)
                <div class="px-4 py-3 bg-zinc-900 border-b border-zinc-800">
                    <div class="text-xs uppercase tracking-widest font-bold text-zinc-400 mb-1">Current Repository</div>
                    <div class="text-sm font-bold text-zinc-100 truncate">{{ $currentRepoName }}</div>
                    <div class="text-xs text-zinc-500 font-mono truncate">{{ $currentRepoPath }}</div>
                </div>
            @endif

            @if(count($recentRepos) > 0)
                <div class="px-4 py-2 bg-zinc-900 border-b border-zinc-800">
                    <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">Recent Repositories</div>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    @foreach($recentRepos as $repo)
                        <div class="group flex items-center justify-between px-4 py-2.5 hover:bg-zinc-900 transition-colors border-b border-zinc-800">
                            <div 
                                wire:click="switchRepo({{ $repo['id'] }})" 
                                class="flex-1 min-w-0 cursor-pointer"
                            >
                                <div class="text-sm font-bold text-zinc-100 truncate flex items-center gap-2">
                                    {{ $repo['name'] }}
                                    @if($currentRepoPath === $repo['path'])
                                        <span class="text-green-400 text-xs">✓</span>
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-500 font-mono truncate">{{ $repo['path'] }}</div>
                                @if($repo['last_opened_at'])
                                    <div class="text-xs text-zinc-600 mt-0.5">{{ $repo['last_opened_at'] }}</div>
                                @endif
                            </div>
                            
                            <flux:button
                                wire:click.stop="removeRecentRepo({{ $repo['id'] }})"
                                variant="ghost"
                                size="xs"
                                icon="trash"
                                class="opacity-0 group-hover:opacity-100 transition-opacity text-red-400"
                            />
                        </div>
                    @endforeach
                </div>
            @else
                @if(!$currentRepoName)
                    <div class="px-4 py-8 text-center">
                        <div class="text-4xl text-zinc-700 mb-2">⊘</div>
                        <div class="text-xs uppercase tracking-wider text-zinc-500">No repositories yet</div>
                    </div>
                @endif
            @endif

            <flux:menu.separator />

            <flux:menu.item 
                icon="folder-open"
                class="uppercase tracking-wider text-xs font-bold"
            >
                Open Repository
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>

    @if($currentRepoPath)
        <div class="text-xs text-zinc-600 font-mono hidden lg:block">
            {{ str_replace($currentRepoName, '', $currentRepoPath) }}
        </div>
    @endif
</div>
