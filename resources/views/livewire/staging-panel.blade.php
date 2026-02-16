<div 
    wire:poll.5s.visible="refreshStatus" 
    x-data="{ 
        showDiscardModal: false, 
        discardTarget: null, 
        discardAll: false,
        selectedFiles: [],
        lastClickedFile: null,
        contextMenu: { show: false, x: 0, y: 0, targetFile: null, targetStaged: false },

        isSelected(path) { 
            return this.selectedFiles.includes(path); 
        },

        handleFileClick(path, staged, event) {
            if (event.metaKey) {
                // Cmd+Click: toggle
                if (this.isSelected(path)) {
                    this.selectedFiles = this.selectedFiles.filter(f => f !== path);
                } else {
                    this.selectedFiles.push(path);
                }
            } else if (event.shiftKey && this.lastClickedFile) {
                // Shift+Click: range select using visible DOM order
                const items = [...this.$el.querySelectorAll('[data-file-path]')];
                const paths = items.map(el => el.dataset.filePath);
                const startIdx = paths.indexOf(this.lastClickedFile);
                const endIdx = paths.indexOf(path);
                if (startIdx !== -1 && endIdx !== -1) {
                    const [from, to] = [Math.min(startIdx, endIdx), Math.max(startIdx, endIdx)];
                    this.selectedFiles = paths.slice(from, to + 1);
                }
            } else {
                // Normal click: clear selection, select one
                this.selectedFiles = [path];
            }
            this.lastClickedFile = path;
            // Always show diff for clicked file
            $wire.selectFile(path, staged);
        },

        showContextMenu(path, staged, event) {
            event.preventDefault();
            // VSCode behavior: right-click on unselected clears + selects; on selected keeps selection
            if (!this.isSelected(path)) {
                this.selectedFiles = [path];
                this.lastClickedFile = path;
            }
            this.contextMenu = { show: true, x: event.clientX, y: event.clientY, targetFile: path, targetStaged: staged };
            // Show diff for right-clicked file
            $wire.selectFile(path, staged);
        },

        hideContextMenu() { this.contextMenu.show = false; },

        get contextMenuFiles() {
            return this.selectedFiles.length > 0 ? this.selectedFiles : (this.contextMenu.targetFile ? [this.contextMenu.targetFile] : []);
        },

        get contextMenuCount() { return this.contextMenuFiles.length; },

        get contextMenuIsStaged() {
            return this.contextMenu.targetStaged;
        },

        clearSelection() { 
            this.selectedFiles = []; 
            this.lastClickedFile = null; 
        },

        selectAllFiles() {
            const items = [...this.$el.querySelectorAll('[data-file-path]')];
            this.selectedFiles = items.map(el => el.dataset.filePath);
        }
    }"
    class="h-full flex flex-col bg-white text-[#4c4f69] font-mono"
    @keyboard-stash.window="selectedFiles.length > 0 ? $wire.stashSelected(selectedFiles).then(() => clearSelection()) : $wire.stashAll()"
    @keyboard-select-all.window="selectAllFiles()"
    @keyboard-escape.window="clearSelection()"
>
    @if($unstagedFiles->isEmpty() && $stagedFiles->isEmpty() && $untrackedFiles->isEmpty())
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center space-y-3">
                <div class="w-20 h-20 mx-auto opacity-60">{!! file_get_contents(resource_path('svg/empty-states/no-changes.svg')) !!}</div>
                <div class="text-[#9ca0b0] uppercase tracking-wider text-xs font-medium">No changes</div>
            </div>
        </div>
    @else
        <div class="border-b border-[#ccd0da] px-4 h-10 flex items-center justify-between">
            <flux:button 
                wire:click="toggleView"
                variant="ghost" 
                size="xs"
                square
                class="text-[#9ca0b0] hover:text-[#6c6f85]"
            >
                @if($treeView)
                    <x-phosphor-list class="w-4 h-4" />
                @else
                    <x-phosphor-folder class="w-4 h-4" />
                @endif
            </flux:button>
            <div class="flex items-center gap-1">
                <flux:tooltip content="Stash" x-bind:content="selectedFiles.length > 0 ? 'Stash ' + selectedFiles.length + ' files' : 'Stash All'">
                    <flux:button 
                        @click="selectedFiles.length > 0 ? $wire.stashSelected(selectedFiles).then(() => clearSelection()) : $wire.stashAll()"
                        wire:loading.attr="disabled"
                        wire:target="stashSelected,stashAll"
                        variant="ghost" 
                        size="xs"
                        square
                        class="text-[#9ca0b0] hover:text-[#6c6f85]"
                    >
                        <x-phosphor-archive class="w-4 h-4" />
                    </flux:button>
                </flux:tooltip>
                <flux:tooltip content="Stage All">
                    <flux:button 
                        wire:click="stageAll"
                        wire:loading.attr="disabled"
                        wire:target="stageAll"
                        variant="ghost" 
                        size="xs"
                        square
                        class="text-[#9ca0b0] hover:text-[#6c6f85]"
                    >
                        <x-phosphor-plus class="w-4 h-4" />
                    </flux:button>
                </flux:tooltip>
                <flux:tooltip content="Unstage All">
                    <flux:button 
                        wire:click="unstageAll"
                        wire:loading.attr="disabled"
                        wire:target="unstageAll"
                        variant="ghost" 
                        size="xs"
                        square
                        class="text-[#9ca0b0] hover:text-[#6c6f85]"
                    >
                        <x-phosphor-minus class="w-4 h-4" />
                    </flux:button>
                </flux:tooltip>
                <flux:tooltip content="Discard All">
                    <flux:button 
                        @click="showDiscardModal = true; discardAll = true; discardTarget = null"
                        variant="ghost" 
                        size="xs"
                        square
                        class="text-[#9ca0b0] hover:text-[#d20f39]"
                    >
                        <x-phosphor-trash class="w-4 h-4" />
                    </flux:button>
                </flux:tooltip>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto">
            @if($stagedFiles->isNotEmpty())
                <div class="border-b border-[#ccd0da]">
                    <div class="sticky top-0 z-10 bg-[#e6e9ef] border-b border-[#ccd0da] px-4 py-2 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="text-xs uppercase tracking-wider font-medium text-[#9ca0b0]">Staged</div>
                            <span class="text-xs text-[#9ca0b0] font-mono">{{ $stagedFiles->count() }}</span>
                        </div>
                    </div>
                    
                    @if($treeView)
                        <x-file-tree :tree="$stagedTree" :staged="true" />
                    @else
                        <div>
                            @foreach($stagedFiles as $file)
                                <div 
                                    wire:key="staged-{{ $file['path'] }}"
                                    data-file-path="{{ $file['path'] }}"
                                    @click="handleFileClick('{{ $file['path'] }}', true, $event)"
                                    @contextmenu="showContextMenu('{{ $file['path'] }}', true, $event)"
                                    class="group px-4 py-1.5 cursor-pointer flex items-center gap-3 animate-slide-in relative"
                                    :class="{ 'bg-[rgba(8,76,207,0.15)]': isSelected('{{ $file['path'] }}'), 'bg-white hover:bg-[#eff1f5] transition-colors duration-150': !isSelected('{{ $file['path'] }}') }"
                                >
                                    <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                        @php
                                            $status = $file['indexStatus'];
                                            $statusConfig = match($status) {
                                                'M' => ['label' => 'M', 'color' => 'yellow', 'icon' => '●'],
                                                'A' => ['label' => 'A', 'color' => 'green', 'icon' => '+'],
                                                'D' => ['label' => 'D', 'color' => 'red', 'icon' => '−'],
                                                'R' => ['label' => 'R', 'color' => 'blue', 'icon' => '→'],
                                                'U' => ['label' => 'U', 'color' => 'orange', 'icon' => 'U'],
                                                default => ['label' => '?', 'color' => 'zinc', 'icon' => '?'],
                                            };
                                        @endphp
                                        <div class="w-2 h-2 rounded-full shrink-0 {{ match($statusConfig['color']) { 'yellow' => 'bg-[#df8e1d]', 'green' => 'bg-[#40a02b]', 'red' => 'bg-[#d20f39]', 'blue' => 'bg-[#084CCF]', 'orange' => 'bg-[#fe640b]', default => 'bg-[#9ca0b0]' } }}"></div>
                                        <flux:tooltip :content="$file['path']" class="min-w-0 flex-1">
                                            <div class="text-sm truncate text-[#5c5f77] group-hover:text-[#4c4f69] transition-colors duration-150">
                                                {{ basename($file['path']) }}
                                            </div>
                                        </flux:tooltip>
                                    </div>
                                    <div 
                                        class="absolute right-0 inset-y-0 flex items-center pr-4 pl-2 opacity-0 group-hover:opacity-100 transition-opacity duration-150"
                                        :class="{ 'bg-[rgba(8,76,207,0.15)]': isSelected('{{ $file['path'] }}'), 'bg-[#eff1f5]': !isSelected('{{ $file['path'] }}') }"
                                    >
                                        <flux:tooltip content="Unstage">
                                            <flux:button 
                                                wire:click.stop="unstageFile('{{ $file['path'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="unstageFile"
                                                variant="ghost" 
                                                size="xs"
                                                square
                                            >
                                                <x-phosphor-minus class="w-3.5 h-3.5" />
                                            </flux:button>
                                        </flux:tooltip>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if($unstagedFiles->isNotEmpty() || $untrackedFiles->isNotEmpty())
                <div>
                    <div class="sticky top-0 z-10 bg-[#e6e9ef] border-b border-[#ccd0da] px-4 py-2 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="text-xs uppercase tracking-wider font-medium text-[#9ca0b0]">Changes</div>
                            <span class="text-xs text-[#9ca0b0] font-mono">{{ $unstagedFiles->count() + $untrackedFiles->count() }}</span>
                        </div>
                    </div>
                    
                    @if($treeView)
                        <x-file-tree :tree="$unstagedTree" :staged="false" />
                    @else
                        <div>
                            @foreach($unstagedFiles->concat($untrackedFiles) as $file)
                                <div 
                                    wire:key="unstaged-{{ $file['path'] }}"
                                    data-file-path="{{ $file['path'] }}"
                                    @click="handleFileClick('{{ $file['path'] }}', false, $event)"
                                    @contextmenu="showContextMenu('{{ $file['path'] }}', false, $event)"
                                    class="group px-4 py-1.5 cursor-pointer flex items-center gap-3 animate-slide-in relative"
                                    :class="{ 'bg-[rgba(8,76,207,0.15)]': isSelected('{{ $file['path'] }}'), 'bg-white hover:bg-[#eff1f5] transition-colors duration-150': !isSelected('{{ $file['path'] }}') }"
                                >
                                    <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                        @php
                                            $status = $file['worktreeStatus'] ?? $file['indexStatus'];
                                            $statusConfig = match($status) {
                                                'M' => ['label' => 'M', 'color' => 'yellow', 'icon' => '●'],
                                                'A' => ['label' => 'A', 'color' => 'green', 'icon' => '+'],
                                                'D' => ['label' => 'D', 'color' => 'red', 'icon' => '−'],
                                                'R' => ['label' => 'R', 'color' => 'blue', 'icon' => '→'],
                                                'U' => ['label' => 'U', 'color' => 'orange', 'icon' => 'U'],
                                                '?' => ['label' => 'U', 'color' => 'green', 'icon' => '?'],
                                                default => ['label' => '?', 'color' => 'zinc', 'icon' => '?'],
                                            };
                                        @endphp
                                        <div class="w-2 h-2 rounded-full shrink-0 {{ match($statusConfig['color']) { 'yellow' => 'bg-[#df8e1d]', 'green' => 'bg-[#40a02b]', 'red' => 'bg-[#d20f39]', 'blue' => 'bg-[#084CCF]', 'orange' => 'bg-[#fe640b]', default => 'bg-[#9ca0b0]' } }}"></div>
                                        <flux:tooltip :content="$file['path']" class="min-w-0 flex-1">
                                            <div class="text-sm truncate text-[#5c5f77] group-hover:text-[#4c4f69] transition-colors duration-150">
                                                {{ basename($file['path']) }}
                                            </div>
                                        </flux:tooltip>
                                    </div>
                                    <div 
                                        class="absolute right-0 inset-y-0 flex items-center gap-1 pr-4 pl-2 opacity-0 group-hover:opacity-100 transition-opacity duration-150"
                                        :class="{ 'bg-[rgba(8,76,207,0.15)]': isSelected('{{ $file['path'] }}'), 'bg-[#eff1f5]': !isSelected('{{ $file['path'] }}') }"
                                    >
                                        <flux:tooltip content="Stage">
                                            <flux:button 
                                                wire:click.stop="stageFile('{{ $file['path'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="stageFile"
                                                variant="ghost" 
                                                size="xs"
                                                square
                                            >
                                                <x-phosphor-plus class="w-3.5 h-3.5" />
                                            </flux:button>
                                        </flux:tooltip>
                                        <flux:tooltip content="Discard">
                                            <flux:button 
                                                @click.stop="showDiscardModal = true; discardAll = false; discardTarget = '{{ $file['path'] }}'"
                                                variant="ghost" 
                                                size="xs"
                                                square
                                                class="text-[#d20f39] hover:text-[#d20f39]"
                                            >
                                                <x-phosphor-arrow-counter-clockwise class="w-3.5 h-3.5" />
                                            </flux:button>
                                        </flux:tooltip>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Right-click context menu --}}
        <div
            x-show="contextMenu.show"
            x-cloak
            @click.outside="hideContextMenu()"
            @keydown.escape.window="hideContextMenu()"
            @scroll.window="hideContextMenu()"
            :style="`position: fixed; left: ${contextMenu.x}px; top: ${contextMenu.y}px; z-index: 50;`"
            class="bg-white border border-[#ccd0da] rounded-lg shadow-lg py-1 min-w-[180px] font-mono text-sm"
        >
            {{-- Stage or Unstage (context-aware) --}}
            <button x-show="!contextMenuIsStaged"
                @click="$wire.stageSelected(contextMenuFiles).then(() => { clearSelection(); hideContextMenu(); })"
                class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#4c4f69]">
                <span x-text="contextMenuCount > 1 ? 'Stage ' + contextMenuCount + ' files' : 'Stage'"></span>
            </button>
            <button x-show="contextMenuIsStaged"
                @click="$wire.unstageSelected(contextMenuFiles).then(() => { clearSelection(); hideContextMenu(); })"
                class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#4c4f69]">
                <span x-text="contextMenuCount > 1 ? 'Unstage ' + contextMenuCount + ' files' : 'Unstage'"></span>
            </button>
            
            {{-- Divider --}}
            <div class="border-t border-[#dce0e8] my-1"></div>
            
            {{-- Stash --}}
            <button @click="$wire.stashSelected(contextMenuFiles).then(() => { clearSelection(); hideContextMenu(); })"
                class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#4c4f69]">
                <span x-text="contextMenuCount > 1 ? 'Stash ' + contextMenuCount + ' files' : 'Stash'"></span>
            </button>
            
            {{-- Divider --}}
            <div class="border-t border-[#dce0e8] my-1"></div>
            
            {{-- Discard --}}
            <button x-show="!contextMenuIsStaged"
                @click="discardTarget = contextMenuFiles; discardAll = false; showDiscardModal = true; hideContextMenu()"
                class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#d20f39]">
                <span x-text="contextMenuCount > 1 ? 'Discard ' + contextMenuCount + ' files' : 'Discard'"></span>
            </button>
        </div>
    @endif

    <flux:modal x-model="showDiscardModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Discard Changes?</flux:heading>
            <flux:subheading class="font-mono">
                <span x-show="discardAll">This will discard all unstaged changes. This action cannot be undone.</span>
                <span x-show="!discardAll">
                    <template x-if="Array.isArray(discardTarget)">
                        <span>This will discard changes to <span class="text-[#4c4f69] font-bold" x-text="discardTarget.length + ' files'"></span>. This action cannot be undone.</span>
                    </template>
                    <template x-if="!Array.isArray(discardTarget)">
                        <span>This will discard changes to <span class="text-[#4c4f69] font-bold" x-text="discardTarget"></span>. This action cannot be undone.</span>
                    </template>
                </span>
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" @click="showDiscardModal = false">Cancel</flux:button>
            <flux:button 
                variant="danger" 
                @click="
                    if (discardAll) {
                        $wire.discardAll();
                    } else if (Array.isArray(discardTarget)) {
                        $wire.discardSelected(discardTarget);
                    } else {
                        $wire.discardFile(discardTarget);
                    }
                    showDiscardModal = false;
                    clearSelection();
                "
            >
                Discard
            </flux:button>
        </div>
    </flux:modal>
</div>
