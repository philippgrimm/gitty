<div x-show="$wire.isInMergeState" 
     x-cloak
     class="absolute inset-0 z-50 bg-white dark:bg-[var(--surface-0)] flex flex-col">
    
    <div class="border-b border-[var(--border-default)] bg-[var(--surface-1)] px-4 h-10 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-[var(--color-red)]"></div>
            <span class="text-sm font-medium text-[var(--text-primary)]">Merge Conflict</span>
            <span class="text-xs text-[var(--text-tertiary)]">{{ $mergeHeadBranch }}</span>
        </div>
        <flux:button wire:click="abortMerge" variant="danger" size="xs">
            Abort Merge
        </flux:button>
    </div>

    @if(empty($conflictFiles))
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 opacity-60">{!! file_get_contents(resource_path('svg/empty-states/no-file.svg')) !!}</div>
                <div class="text-[var(--text-tertiary)] uppercase tracking-wider text-sm">No conflicts remaining</div>
                <div class="text-[var(--text-tertiary)] text-xs mt-2">All conflicts have been resolved</div>
            </div>
        </div>
    @else
        <div class="flex-1 flex overflow-hidden">
            <div class="w-[200px] border-r border-[var(--border-default)] bg-white dark:bg-[var(--surface-0)] flex flex-col">
                <div class="px-4 py-2 border-b border-[var(--border-default)] bg-[var(--surface-1)]">
                    <span class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)]">Conflicted Files</span>
                </div>
                
                <div class="flex-1 overflow-y-auto">
                    @foreach($conflictFiles as $file)
                        <div wire:click="selectFile('{{ $file['path'] }}')"
                             class="px-4 py-2 cursor-pointer border-b border-[var(--border-subtle)] hover:bg-[var(--surface-0)] transition-colors {{ $selectedFile === $file['path'] ? 'bg-[var(--accent-muted)]' : '' }}">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-[var(--color-red)] shrink-0"></div>
                                <span class="text-sm text-[var(--text-primary)] truncate">{{ basename($file['path']) }}</span>
                            </div>
                            @if(dirname($file['path']) !== '.')
                                <div class="text-xs text-[var(--text-tertiary)] truncate ml-4 mt-0.5">{{ dirname($file['path']) }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-[var(--border-default)] p-2">
                    <flux:button wire:click="abortMerge" variant="danger" size="sm" class="w-full">
                        Abort Merge
                    </flux:button>
                </div>
            </div>

            @if($selectedFile)
                <div class="flex-1 flex flex-col overflow-hidden">
                    @if($isBinary)
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center space-y-4">
                                <div class="text-[var(--text-tertiary)] uppercase tracking-wider text-sm">Binary File Conflict</div>
                                <div class="text-[var(--text-secondary)] text-xs">Choose which version to keep</div>
                                <div class="flex gap-3 justify-center mt-6">
                                    <flux:button wire:click="acceptOurs(); resolveFile()" variant="primary" size="sm">
                                        Choose Ours
                                    </flux:button>
                                    <flux:button wire:click="acceptTheirs(); resolveFile()" variant="primary" size="sm">
                                        Choose Theirs
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex-1 flex overflow-hidden">
                            <div class="flex-1 flex flex-col border-r border-[var(--border-default)]">
                                <div class="px-4 py-2 border-b border-[var(--border-default)] bg-[var(--surface-1)]">
                                    <span class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)]">Ours (Current Branch)</span>
                                </div>
                                <div class="flex-1 overflow-auto p-4">
                                    <pre class="font-mono text-sm text-[var(--text-primary)] whitespace-pre-wrap">{{ $oursContent }}</pre>
                                </div>
                            </div>

                            <div class="flex-1 flex flex-col border-r border-[var(--border-default)]">
                                <div class="px-4 py-2 border-b border-[var(--border-default)] bg-[var(--surface-1)]">
                                    <span class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)]">Result (Editable)</span>
                                </div>
                                <div class="flex-1 overflow-auto p-4">
                                    <textarea wire:model.live="resultContent"
                                              class="w-full h-full font-mono text-sm text-[var(--text-primary)] bg-transparent border-none outline-none resize-none"
                                              spellcheck="false"></textarea>
                                </div>
                            </div>

                            <div class="flex-1 flex flex-col">
                                <div class="px-4 py-2 border-b border-[var(--border-default)] bg-[var(--surface-1)]">
                                    <span class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)]">Theirs (Incoming Branch)</span>
                                </div>
                                <div class="flex-1 overflow-auto p-4">
                                    <pre class="font-mono text-sm text-[var(--text-primary)] whitespace-pre-wrap">{{ $theirsContent }}</pre>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-[var(--border-default)] px-4 py-3 bg-white dark:bg-[var(--surface-0)] flex items-center gap-3">
                            <flux:button wire:click="acceptOurs" variant="ghost" size="sm">
                                Accept Ours
                            </flux:button>
                            <flux:button wire:click="acceptBoth" variant="ghost" size="sm">
                                Accept Both
                            </flux:button>
                            <flux:button wire:click="acceptTheirs" variant="ghost" size="sm">
                                Accept Theirs
                            </flux:button>
                            <div class="flex-1"></div>
                            <flux:button wire:click="resolveFile" variant="primary" size="sm">
                                Mark Resolved
                            </flux:button>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div class="text-[var(--text-tertiary)] uppercase tracking-wider text-sm">Select a file to resolve</div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if($showAbortModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="$wire.cancelAbortMerge()">
            <div class="bg-white dark:bg-[var(--surface-0)] rounded-lg shadow-lg max-w-md w-full mx-4 border border-[var(--border-default)]">
                <div class="px-6 py-4 border-b border-[var(--border-default)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Abort Merge?</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-[var(--text-secondary)]">
                        This will abort the merge and return to the state before the merge started. All conflict resolutions will be lost.
                    </p>
                </div>
                <div class="px-6 py-4 border-t border-[var(--border-default)] flex justify-end gap-3">
                    <flux:button wire:click="cancelAbortMerge" variant="ghost" size="sm">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="confirmAbortMerge" variant="danger" size="sm">
                        Abort Merge
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
