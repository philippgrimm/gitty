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
    class="h-full flex flex-col bg-zinc-950 text-zinc-100 font-mono border-r-2 border-zinc-800"
>
    @if($unstagedFiles->isEmpty() && $stagedFiles->isEmpty() && $untrackedFiles->isEmpty())
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center space-y-3">
                <div class="text-6xl text-zinc-700">✓</div>
                <div class="text-zinc-500 uppercase tracking-widest text-xs font-bold">No changes</div>
            </div>
        </div>
    @else
        <div class="border-b-2 border-zinc-800 px-4 py-2 flex items-center justify-end">
            <flux:button 
                wire:click="toggleView"
                variant="ghost" 
                size="sm"
                class="text-xs uppercase tracking-wider"
            >
                @if($treeView)
                    <span class="flex items-center gap-2">
                        <span>☰</span>
                        <span>Flat</span>
                    </span>
                @else
                    <span class="flex items-center gap-2">
                        <span>🌳</span>
                        <span>Tree</span>
                    </span>
                @endif
            </flux:button>
        </div>
        <div class="flex-1 overflow-y-auto">
            @if($stagedFiles->isNotEmpty())
                <div class="border-b-2 border-zinc-800">
                    <div class="sticky top-0 bg-zinc-900 border-b border-zinc-800 px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">Staged Changes</div>
                            <flux:badge variant="solid" color="green" class="font-mono text-xs">{{ $stagedFiles->count() }}</flux:badge>
                        </div>
                        <flux:button 
                            wire:click="unstageAll" 
                            variant="ghost" 
                            size="sm"
                            class="text-xs uppercase tracking-wider"
                        >
                            Unstage All (⌘⇧U)
                        </flux:button>
                    </div>
                    
                    @if($treeView)
                        <x-file-tree :tree="$stagedTree" :staged="true" />
                    @else
                        <div class="divide-y divide-zinc-800">
                            @foreach($stagedFiles as $file)
                                <div 
                                    wire:click="selectFile('{{ $file['path'] }}', true)"
                                    class="group px-4 py-2.5 hover:bg-zinc-900 cursor-pointer transition-colors flex items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
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
                                        <flux:badge variant="solid" color="{{ $statusConfig['color'] }}" class="font-mono text-xs w-6 h-6 flex items-center justify-center">
                                            {{ $statusConfig['icon'] }}
                                        </flux:badge>
                                        <flux:tooltip :content="$file['path']">
                                            <div class="text-sm truncate text-zinc-200 group-hover:text-white transition-colors">
                                                {{ basename($file['path']) }}
                                            </div>
                                        </flux:tooltip>
                                    </div>
                                    <flux:button 
                                        wire:click.stop="unstageFile('{{ $file['path'] }}')"
                                        variant="ghost" 
                                        size="sm"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <span class="text-xs">−</span>
                                    </flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if($unstagedFiles->isNotEmpty() || $untrackedFiles->isNotEmpty())
                <div>
                    <div class="sticky top-0 bg-zinc-900 border-b border-zinc-800 px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">Changes</div>
                            <flux:badge variant="solid" color="zinc" class="font-mono text-xs">{{ $unstagedFiles->count() + $untrackedFiles->count() }}</flux:badge>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:button 
                                wire:click="stageAll" 
                                variant="ghost" 
                                size="sm"
                                class="text-xs uppercase tracking-wider"
                            >
                                Stage All (⌘⇧K)
                            </flux:button>
                            <flux:button 
                                @click="showDiscardModal = true; discardAll = true; discardTarget = null"
                                variant="ghost" 
                                size="sm"
                                class="text-xs uppercase tracking-wider text-red-400 hover:text-red-300"
                            >
                                Discard All
                            </flux:button>
                        </div>
                    </div>
                    
                    @if($treeView)
                        <x-file-tree :tree="$unstagedTree" :staged="false" />
                    @else
                        <div class="divide-y divide-zinc-800">
                            @foreach($unstagedFiles->concat($untrackedFiles) as $file)
                                <div 
                                    wire:click="selectFile('{{ $file['path'] }}', false)"
                                    class="group px-4 py-2.5 hover:bg-zinc-900 cursor-pointer transition-colors flex items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
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
                                        <flux:badge variant="solid" color="{{ $statusConfig['color'] }}" class="font-mono text-xs w-6 h-6 flex items-center justify-center">
                                            {{ $statusConfig['icon'] }}
                                        </flux:badge>
                                        <flux:tooltip :content="$file['path']">
                                            <div class="text-sm truncate text-zinc-200 group-hover:text-white transition-colors">
                                                {{ basename($file['path']) }}
                                            </div>
                                        </flux:tooltip>
                                    </div>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <flux:button 
                                            wire:click.stop="stageFile('{{ $file['path'] }}')"
                                            variant="ghost" 
                                            size="sm"
                                        >
                                            <span class="text-xs">+</span>
                                        </flux:button>
                                        <flux:button 
                                            @click.stop="showDiscardModal = true; discardAll = false; discardTarget = '{{ $file['path'] }}'"
                                            variant="ghost" 
                                            size="sm"
                                            class="text-red-400 hover:text-red-300"
                                        >
                                            <span class="text-xs">×</span>
                                        </flux:button>
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
                <span x-show="!discardAll">This will discard changes to <span class="text-zinc-100 font-bold" x-text="discardTarget"></span>. This action cannot be undone.</span>
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
