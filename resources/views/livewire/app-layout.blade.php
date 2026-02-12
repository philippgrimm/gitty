<div class="h-screen w-screen flex flex-col bg-zinc-950 text-zinc-100 font-mono overflow-hidden">
    <div class="border-b-2 border-zinc-800 bg-zinc-900 px-4 py-2">
        @livewire('repo-switcher', key('repo-switcher'))
    </div>

    @if(empty($repoPath))
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center space-y-6">
                <div class="text-8xl text-zinc-700">⊘</div>
                <div class="text-zinc-500 uppercase tracking-widest text-sm font-bold">No Repository Selected</div>
                <div class="text-zinc-600 text-xs">Open a git repository to get started</div>
            </div>
        </div>
    @else
        <div class="border-b-2 border-zinc-800 bg-zinc-900 px-4 py-2 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <flux:button 
                    wire:click="toggleSidebar" 
                    variant="ghost" 
                    size="sm"
                    square
                    icon="{{ $sidebarCollapsed ? 'bars-3' : 'bars-3' }}"
                />
                @livewire('branch-manager', ['repoPath' => $repoPath], key('branch-manager'))
            </div>
            <div class="flex items-center gap-2">
                @livewire('sync-panel', ['repoPath' => $repoPath], key('sync-panel'))
            </div>
        </div>

        <div class="flex-1 flex overflow-hidden">
            <div 
                class="border-r-2 border-zinc-800 bg-zinc-950 transition-all duration-300 overflow-hidden"
                style="width: {{ $sidebarCollapsed ? '0px' : '250px' }}; min-width: {{ $sidebarCollapsed ? '0px' : '250px' }};"
            >
                @if(!$sidebarCollapsed)
                    @livewire('repo-sidebar', ['repoPath' => $repoPath], key('repo-sidebar'))
                @endif
            </div>

            <div class="flex-1 flex overflow-hidden">
                <div class="w-1/3 flex flex-col border-r-2 border-zinc-800 overflow-hidden">
                    <div class="flex-1 overflow-hidden">
                        @livewire('staging-panel', ['repoPath' => $repoPath], key('staging-panel'))
                    </div>
                    <div class="h-64 border-t-2 border-zinc-800 overflow-hidden">
                        @livewire('commit-panel', ['repoPath' => $repoPath], key('commit-panel'))
                    </div>
                </div>

                <div class="flex-1 overflow-hidden">
                    @livewire('diff-viewer', ['repoPath' => $repoPath], key('diff-viewer'))
                </div>
            </div>
        </div>
    @endif
</div>
