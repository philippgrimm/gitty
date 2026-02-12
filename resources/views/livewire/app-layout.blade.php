<div 
    class="h-screen w-screen flex flex-col bg-zinc-950 text-zinc-100 font-mono overflow-hidden"
    @keydown.window.meta.enter.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-commit')"
    @keydown.window.meta.shift.enter.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-commit-push')"
    @keydown.window.meta.shift.k.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-stage-all')"
    @keydown.window.meta.shift.u.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-unstage-all')"
    @keydown.window.meta.b.prevent="if (!$wire.repoPath) return; $wire.toggleSidebar()"
    @keydown.window.escape.prevent="$wire.$dispatch('keyboard-escape')"
>
    @livewire('error-banner', key('error-banner'))
    
    <div class="border-b border-zinc-800 bg-zinc-900 px-4 py-3 flex items-center justify-between gap-4" style="box-shadow: var(--shadow-sm); -webkit-app-region: drag;">
        <div class="flex items-center gap-4 flex-1 min-w-0">
            <span class="text-xs font-semibold tracking-wider text-zinc-500 uppercase select-none">gitty</span>
            @if(!empty($repoPath))
                <flux:button 
                    wire:click="toggleSidebar" 
                    variant="ghost" 
                    size="sm"
                    square
                    icon="{{ $sidebarCollapsed ? 'bars-3' : 'bars-3' }}"
                    style="-webkit-app-region: no-drag;"
                />
            @endif
            
            <div class="flex-1 min-w-0" style="-webkit-app-region: no-drag;">
                @livewire('repo-switcher', key('repo-switcher'))
            </div>
        </div>

        @if(!empty($repoPath))
            <div class="flex items-center gap-4" style="-webkit-app-region: no-drag;">
                @livewire('branch-manager', ['repoPath' => $repoPath], key('branch-manager-' . $repoPath))
                @livewire('sync-panel', ['repoPath' => $repoPath], key('sync-panel-' . $repoPath))
            </div>
        @endif
    </div>

    @if(empty($repoPath))
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center space-y-6">
                <div class="w-24 h-24 mx-auto opacity-60">
                    {!! file_get_contents(resource_path('svg/empty-states/no-repo.svg')) !!}
                </div>
                <div class="text-zinc-400 uppercase tracking-wider text-sm font-medium">No Repository Selected</div>
                <div class="text-zinc-400 text-xs">Open a git repository to get started</div>
            </div>
        </div>
    @else

        <div class="flex-1 flex overflow-hidden">
            <div 
                class="border-r border-zinc-800 bg-zinc-950 transition-all duration-200 ease-out overflow-hidden"
                style="width: {{ $sidebarCollapsed ? '0px' : '250px' }}; min-width: {{ $sidebarCollapsed ? '0px' : '250px' }};"
            >
                @if(!$sidebarCollapsed)
                    @livewire('repo-sidebar', ['repoPath' => $repoPath], key('repo-sidebar-' . $repoPath))
                @endif
            </div>

            <div class="flex-1 flex overflow-hidden">
                <div class="w-1/3 flex flex-col border-r border-zinc-800 overflow-hidden">
                    <div class="flex-1 overflow-hidden">
                        @livewire('staging-panel', ['repoPath' => $repoPath], key('staging-panel-' . $repoPath))
                    </div>
                    <div class="h-64 border-t border-zinc-800/50 overflow-hidden">
                        @livewire('commit-panel', ['repoPath' => $repoPath], key('commit-panel-' . $repoPath))
                    </div>
                </div>

                <div class="flex-1 overflow-hidden">
                    @livewire('diff-viewer', ['repoPath' => $repoPath], key('diff-viewer-' . $repoPath))
                </div>
            </div>
        </div>

        <div class="h-7 border-t border-zinc-800 bg-zinc-900 px-4 flex items-center gap-4 text-xs font-mono text-zinc-400" style="box-shadow: var(--shadow-sm);">
            @if(!empty($this->statusBarData))
                <span class="flex items-center gap-1.5">
                    <span class="text-amber-500">⎇</span>
                    <span class="text-zinc-300">{{ $this->statusBarData['branch'] }}</span>
                </span>
                @if($this->statusBarData['ahead'] > 0 || $this->statusBarData['behind'] > 0)
                    <span class="flex items-center gap-1.5">
                        @if($this->statusBarData['ahead'] > 0)
                            <span class="text-green-400">↑{{ $this->statusBarData['ahead'] }}</span>
                        @endif
                        @if($this->statusBarData['behind'] > 0)
                            <span class="text-orange-400">↓{{ $this->statusBarData['behind'] }}</span>
                        @endif
                    </span>
                @endif
            @endif
            <span class="ml-auto text-zinc-500">{{ basename($repoPath) }}</span>
        </div>
    @endif
</div>
