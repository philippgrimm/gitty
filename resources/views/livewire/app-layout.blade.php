<div 
    class="h-screen w-screen flex flex-col bg-[#eff1f5] text-[#4c4f69] font-mono overflow-hidden"
    @keydown.window.meta.enter.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-commit')"
    @keydown.window.meta.shift.enter.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-commit-push')"
    @keydown.window.meta.shift.k.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-stage-all')"
    @keydown.window.meta.shift.u.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-unstage-all')"
    @keydown.window.meta.b.prevent="if (!$wire.repoPath) return; $wire.toggleSidebar()"
    @keydown.window.escape.prevent="$wire.$dispatch('keyboard-escape')"
>
    @livewire('error-banner', key('error-banner'))
    
    <div class="border-b border-[#ccd0da] bg-[#e6e9ef] px-3 flex items-center gap-2 h-9" style="box-shadow: var(--shadow-sm); -webkit-app-region: drag;">
        {{-- Traffic light drag spacer (macOS window controls) --}}
        <div class="w-16" style="-webkit-app-region: drag;"></div>

        {{-- Sidebar toggle button --}}
        <div style="-webkit-app-region: no-drag;">
            <flux:button wire:click="toggleSidebar" variant="ghost" size="xs" square class="text-[#9ca0b0] hover:text-[#6c6f85] flex items-center justify-center">
                <x-phosphor-sidebar-simple class="w-4 h-4" />
            </flux:button>
        </div>

        {{-- Repo switcher --}}
        <div style="-webkit-app-region: no-drag;">
            @livewire('repo-switcher', key('repo-switcher'))
        </div>

        {{-- Branch manager (only when repo is open) --}}
        @if(!empty($repoPath))
            <div style="-webkit-app-region: no-drag;">
                @livewire('branch-manager', ['repoPath' => $repoPath], key('branch-manager-' . $repoPath))
            </div>
        @endif

        {{-- Flexible spacer --}}
        <div class="flex-1" style="-webkit-app-region: drag;"></div>

        {{-- Sync buttons (only when repo is open) --}}
        @if(!empty($repoPath))
            <div style="-webkit-app-region: no-drag;">
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
                <div class="text-[#9ca0b0] uppercase tracking-wider text-sm font-medium">No Repository Selected</div>
                <div class="text-[#9ca0b0] text-xs">Open a git repository to get started</div>
            </div>
        </div>
    @else

        <div class="flex-1 flex overflow-hidden">
            <div 
                class="border-r border-[#ccd0da] bg-[#eff1f5] transition-all duration-200 ease-out overflow-hidden"
                style="width: {{ $sidebarCollapsed ? '0px' : '250px' }}; min-width: {{ $sidebarCollapsed ? '0px' : '250px' }};"
            >
                @if(!$sidebarCollapsed)
                    @livewire('repo-sidebar', ['repoPath' => $repoPath], key('repo-sidebar-' . $repoPath))
                @endif
            </div>

            <div class="flex-1 flex overflow-hidden">
                <div class="w-1/3 flex flex-col border-r border-[#ccd0da] overflow-hidden">
                    <div class="flex-1 overflow-hidden">
                        @livewire('staging-panel', ['repoPath' => $repoPath], key('staging-panel-' . $repoPath))
                    </div>
                    <div class="h-64 border-t border-[#dce0e8] overflow-hidden">
                        @livewire('commit-panel', ['repoPath' => $repoPath], key('commit-panel-' . $repoPath))
                    </div>
                </div>

                <div class="flex-1 overflow-hidden">
                    @livewire('diff-viewer', ['repoPath' => $repoPath], key('diff-viewer-' . $repoPath))
                </div>
            </div>
        </div>


    @endif
</div>
