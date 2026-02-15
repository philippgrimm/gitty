<div 
    wire:poll.3s.visible="refreshStatus" 
    x-data="{ 
        showDiscardModal: false, 
        discardTarget: null, 
        discardAll: false,
        resumePollingTimer: null,
        startResumeTimer() {
            clearTimeout(this.resumePollingTimer);
            this.resumePollingTimer = setTimeout(() => {
                $wire.resumePolling();
            }, 5000);
        }
    }"
    @status-updated.window="startResumeTimer()"
    class="h-full flex flex-col bg-white text-[#4c4f69] font-mono border-r border-[#ccd0da]"
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
                    <div class="sticky top-0 bg-[#e6e9ef] border-b border-[#ccd0da] px-4 py-2 flex items-center justify-between">
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
                                    wire:click="selectFile('{{ $file['path'] }}', true)"
                                    class="group px-4 py-1.5 hover:bg-[#eff1f5] cursor-pointer transition-colors flex items-center justify-between gap-3 animate-slide-in"
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
                                        <div class="w-2.5 h-2.5 rounded-full shrink-0 {{ match($statusConfig['color']) { 'yellow' => 'bg-[#df8e1d]', 'green' => 'bg-[#40a02b]', 'red' => 'bg-[#d20f39]', 'blue' => 'bg-[#084CCF]', 'orange' => 'bg-[#fe640b]', default => 'bg-[#9ca0b0]' } }}"></div>
                                        <flux:tooltip :content="$file['path']">
                                            <div class="text-sm truncate text-[#5c5f77] group-hover:text-[#4c4f69] transition-colors">
                                                {{ basename($file['path']) }}
                                            </div>
                                        </flux:tooltip>
                                    </div>
                                    <flux:tooltip content="Unstage">
                                        <flux:button 
                                            wire:click.stop="unstageFile('{{ $file['path'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="unstageFile"
                                            variant="ghost" 
                                            size="xs"
                                            square
                                            class="opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            <x-phosphor-minus class="w-3.5 h-3.5" />
                                        </flux:button>
                                    </flux:tooltip>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if($unstagedFiles->isNotEmpty() || $untrackedFiles->isNotEmpty())
                <div>
                    <div class="sticky top-0 bg-[#e6e9ef] border-b border-[#ccd0da] px-4 py-2 flex items-center justify-between">
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
                                    wire:click="selectFile('{{ $file['path'] }}', false)"
                                    class="group px-4 py-1.5 hover:bg-[#eff1f5] cursor-pointer transition-colors flex items-center justify-between gap-3 animate-slide-in"
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
                                        <div class="w-2.5 h-2.5 rounded-full shrink-0 {{ match($statusConfig['color']) { 'yellow' => 'bg-[#df8e1d]', 'green' => 'bg-[#40a02b]', 'red' => 'bg-[#d20f39]', 'blue' => 'bg-[#084CCF]', 'orange' => 'bg-[#fe640b]', default => 'bg-[#9ca0b0]' } }}"></div>
                                        <flux:tooltip :content="$file['path']">
                                            <div class="text-sm truncate text-[#5c5f77] group-hover:text-[#4c4f69] transition-colors">
                                                {{ basename($file['path']) }}
                                            </div>
                                        </flux:tooltip>
                                    </div>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
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
    @endif

    <flux:modal x-model="showDiscardModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Discard Changes?</flux:heading>
            <flux:subheading class="font-mono">
                <span x-show="discardAll">This will discard all unstaged changes. This action cannot be undone.</span>
                <span x-show="!discardAll">This will discard changes to <span class="text-[#4c4f69] font-bold" x-text="discardTarget"></span>. This action cannot be undone.</span>
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" @click="showDiscardModal = false">Cancel</flux:button>
            <flux:button 
                variant="danger" 
                @click="
                    if (discardAll) {
                        $wire.discardAll();
                    } else {
                        $wire.discardFile(discardTarget);
                    }
                    showDiscardModal = false;
                "
            >
                Discard
            </flux:button>
        </div>
    </flux:modal>
</div>
