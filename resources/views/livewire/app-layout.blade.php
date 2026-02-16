<div 
    class="h-screen w-screen flex flex-col bg-[#eff1f5] text-[#4c4f69] font-mono overflow-hidden"
    @keydown.window.meta.enter.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-commit')"
    @keydown.window.meta.shift.enter.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-commit-push')"
    @keydown.window.meta.shift.k.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-stage-all')"
    @keydown.window.meta.shift.u.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-unstage-all')"
    @keydown.window.meta.shift.s.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-stash')"
    @keydown.window.meta.a.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-select-all')"
    @keydown.window.meta.b.prevent="if (!$wire.repoPath) return; $wire.toggleSidebar()"
    @keydown.window.meta.k.prevent="if(!$event.shiftKey) $wire.$dispatch('open-command-palette')"
    @keydown.window.meta.shift.p.prevent="$wire.$dispatch('open-command-palette')"
    @keydown.window.escape.prevent="$wire.$dispatch('keyboard-escape')"
>
    @livewire('error-banner', key('error-banner'))
    @livewire('command-palette', key('command-palette'))
    
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

            <div class="flex-1 flex overflow-hidden"
                 x-data="{
                     panelWidth: null,
                     isDragging: false,
                     startX: 0,
                     startWidth: 0,
                     init() {
                         const saved = localStorage.getItem('gitty-panel-width');
                         if (saved && !isNaN(parseInt(saved))) {
                             this.panelWidth = parseInt(saved);
                         }
                     },
                     get effectiveWidth() {
                         if (this.panelWidth) return this.panelWidth;
                         return Math.round(this.$el.offsetWidth / 3);
                     },
                     startDrag(e) {
                         this.isDragging = true;
                         this.startX = e.clientX;
                         this.startWidth = this.effectiveWidth;
                         document.body.style.cursor = 'col-resize';
                         document.body.style.userSelect = 'none';
                     },
                     onDrag(e) {
                         if (!this.isDragging) return;
                         const delta = e.clientX - this.startX;
                         const maxWidth = Math.round(this.$el.offsetWidth * 0.5);
                         this.panelWidth = Math.min(Math.max(this.startWidth + delta, 200), maxWidth);
                     },
                     stopDrag() {
                         if (!this.isDragging) return;
                         this.isDragging = false;
                         document.body.style.cursor = '';
                         document.body.style.userSelect = '';
                         if (this.panelWidth) {
                             localStorage.setItem('gitty-panel-width', this.panelWidth.toString());
                         }
                     }
                 }"
                 @mousemove.window="onDrag($event)"
                 @mouseup.window="stopDrag()"
            >
                {{-- Staging + Commit Panel --}}
                <div class="flex flex-col overflow-hidden"
                     :style="'width: ' + effectiveWidth + 'px'"
                >
                    <div class="flex-1 overflow-hidden">
                        @livewire('staging-panel', ['repoPath' => $repoPath], key('staging-panel-' . $repoPath))
                    </div>
                    <div class="border-t border-[#dce0e8]">
                        @livewire('commit-panel', ['repoPath' => $repoPath], key('commit-panel-' . $repoPath))
                    </div>
                </div>

                {{-- Resize Handle --}}
                <div @mousedown.prevent="startDrag($event)"
                     class="w-[5px] flex-shrink-0 cursor-col-resize relative group/resize"
                >
                    <div class="absolute inset-y-0 left-[2px] w-px bg-[#ccd0da] group-hover/resize:bg-[#084CCF] transition-colors"
                         :class="isDragging && 'bg-[#084CCF]'"
                    ></div>
                </div>

                {{-- Diff Viewer --}}
                <div class="flex-1 overflow-hidden">
                    @livewire('diff-viewer', ['repoPath' => $repoPath], key('diff-viewer-' . $repoPath))
                </div>
            </div>
        </div>


    @endif
</div>
