<div 
    wire:poll.30s.visible="handleStatusUpdated" 
    x-data="{ 
        contextMenu: { show: false, x: 0, y: 0, targetSha: null },

        showContextMenu(sha, event) {
            event.preventDefault();
            this.contextMenu = { show: true, x: event.clientX, y: event.clientY, targetSha: sha };
        },

        hideContextMenu() { 
            this.contextMenu.show = false; 
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            this.hideContextMenu();
        }
    }"
    class="h-full flex flex-col bg-white dark:bg-[var(--surface-0)] text-[var(--text-primary)] font-mono"
>
    @if($commits->isEmpty())
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center space-y-3">
                <div class="w-20 h-20 mx-auto opacity-60">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="text-[var(--text-tertiary)] uppercase tracking-wider text-xs font-medium">No commits yet</div>
            </div>
        </div>
    @else
        {{-- Header bar --}}
        <div class="sticky top-0 z-10 bg-[var(--surface-1)] border-b border-[var(--border-default)] px-4 py-2 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-phosphor-clock-counter-clockwise-light class="w-4 h-4 text-[var(--text-tertiary)]" />
                <div class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)]">History</div>
                <span class="text-xs text-[var(--text-tertiary)] font-mono">{{ $commits->count() }}</span>
            </div>
            <flux:tooltip content="Close History (⌘H)">
                <flux:button 
                    @click="$dispatch('toggle-history-panel')"
                    variant="ghost" 
                    size="xs"
                    square
                    class="text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]"
                >
                    <x-phosphor-x class="w-4 h-4" />
                </flux:button>
            </flux:tooltip>
        </div>

        {{-- Commit list --}}
        <div class="flex-1 overflow-y-auto">
            @foreach($commits as $commit)
                <div 
                    wire:key="commit-{{ $commit->sha }}"
                    wire:click="selectCommit('{{ $commit->sha }}')"
                    @contextmenu="showContextMenu('{{ $commit->sha }}', $event)"
                    class="px-4 py-2 cursor-pointer border-b border-[var(--border-subtle)] transition-colors duration-150"
                    :class="$wire.selectedCommitSha === '{{ $commit->sha }}' ? 'bg-[rgba(8,76,207,0.15)]' : 'bg-white dark:bg-[var(--surface-0)] hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)]'"
                >
                    {{-- Line 1: SHA | Author | Date --}}
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <div class="flex items-center gap-3 min-w-0">
                            <flux:tooltip :content="$commit->sha" delay="500">
                                <span class="text-xs text-[#084CCF] font-mono">{{ $commit->shortSha }}</span>
                            </flux:tooltip>
                            @if(!empty($commit->author))
                                <span class="text-sm text-[var(--text-primary)] truncate">{{ $commit->author }}</span>
                            @endif
                        </div>
                        @if(!empty($commit->date))
                            <span class="text-xs text-[var(--text-tertiary)] shrink-0">{{ $commit->date }}</span>
                        @endif
                    </div>

                    {{-- Line 2: Message --}}
                    <div class="text-sm text-[var(--text-secondary)] truncate">{{ $commit->message }}</div>

                    {{-- Line 3: Ref badges (if any) --}}
                    @if(!empty($commit->refs))
                        <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                            @foreach($commit->refs as $ref)
                                @php
                                    $isHead = str_contains($ref, 'HEAD');
                                    $isBranch = str_starts_with($ref, 'HEAD ->') || (!str_contains($ref, '/') && !str_contains($ref, 'tag:'));
                                    $isRemote = str_contains($ref, '/') && !str_contains($ref, 'tag:');
                                    $isTag = str_contains($ref, 'tag:');
                                    
                                    $displayRef = str_replace('HEAD -> ', '', $ref);
                                    $displayRef = str_replace('tag: ', '', $displayRef);
                                    
                                    if ($isHead && !str_contains($ref, '->')) {
                                        $bgColor = '#fe640b';
                                        $textColor = '#ffffff';
                                    } elseif ($isTag) {
                                        $bgColor = '#8839ef';
                                        $textColor = '#ffffff';
                                    } elseif ($isRemote) {
                                        $bgColor = '#179299';
                                        $textColor = '#ffffff';
                                    } else {
                                        $bgColor = '#40a02b';
                                        $textColor = '#ffffff';
                                    }
                                @endphp
                                <span 
                                    class="px-1.5 py-0.5 rounded text-xs font-medium"
                                    style="background-color: {{ $bgColor }}; color: {{ $textColor }};"
                                >
                                    {{ $displayRef }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Load More button --}}
            @if($hasMore)
                <div class="p-4 flex justify-center">
                    <flux:button 
                        wire:click="loadMore"
                        wire:loading.attr="disabled"
                        wire:target="loadMore"
                        variant="subtle" 
                        size="sm"
                    >
                        <span wire:loading.remove wire:target="loadMore">Load More</span>
                        <span wire:loading wire:target="loadMore">Loading...</span>
                    </flux:button>
                </div>
            @endif
        </div>
    @endif

    {{-- Right-click context menu --}}
    <div
        x-show="contextMenu.show"
        x-cloak
        @click.outside="hideContextMenu()"
        @keydown.escape.window="hideContextMenu()"
        @scroll.window="hideContextMenu()"
        :style="`position: fixed; left: ${contextMenu.x}px; top: ${contextMenu.y}px; z-index: 50;`"
        class="bg-white dark:bg-[var(--surface-0)] border border-[var(--border-default)] rounded-lg shadow-lg py-1 min-w-[180px] font-mono text-sm"
    >
        <button 
            @click="copyToClipboard(contextMenu.targetSha)"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)]"
        >
            Copy SHA
        </button>
        <button 
            @click="copyToClipboard($wire.commits.find(c => c.sha === contextMenu.targetSha)?.message || '')"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)]"
        >
            Copy Message
        </button>
        <div class="border-t border-[var(--border-subtle)] my-1"></div>
        <button 
            @click="$wire.promptRevert(contextMenu.targetSha, $wire.commits.find(c => c.sha === contextMenu.targetSha)?.message || ''); hideContextMenu()"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
        >
            <x-phosphor-arrow-counter-clockwise class="w-4 h-4" />
            <span>Revert this Commit</span>
        </button>
        <button 
            @click="$wire.promptCherryPick(contextMenu.targetSha, $wire.commits.find(c => c.sha === contextMenu.targetSha)?.message || ''); hideContextMenu()"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
        >
            <x-phosphor-git-commit class="w-4 h-4" />
            <span>Cherry-pick this Commit</span>
        </button>
        <button 
            @click="$wire.promptReset(contextMenu.targetSha, $wire.commits.find(c => c.sha === contextMenu.targetSha)?.message || ''); hideContextMenu()"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
        >
            <x-phosphor-arrow-bend-up-left class="w-4 h-4" />
            <span>Reset to this Commit</span>
        </button>
        <div class="border-t border-[var(--border-subtle)] my-1"></div>
        <button 
            @click="$wire.promptInteractiveRebase(contextMenu.targetSha); hideContextMenu()"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
        >
            <x-phosphor-git-merge class="w-4 h-4" />
            <span>Interactive Rebase</span>
        </button>
    </div>

    {{-- Reset Modal --}}
    <flux:modal wire:model="showResetModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Reset to Commit</flux:heading>
            <flux:subheading class="font-mono text-xs">{{ $resetTargetSha ? substr($resetTargetSha, 0, 8) : '' }} — {{ $resetTargetMessage }}</flux:subheading>
        </div>
        
        <div class="space-y-3">
            <label class="flex items-start gap-3 p-3 rounded border cursor-pointer transition-colors"
                :class="$wire.resetMode === 'soft' ? 'border-[#084CCF] bg-[rgba(8,76,207,0.05)]' : 'border-[var(--border-default)] hover:border-[var(--border-strong)]'">
                <input type="radio" wire:model.live="resetMode" value="soft" class="mt-0.5">
                <div>
                    <div class="font-medium text-sm text-[var(--text-primary)]">Soft Reset</div>
                    <div class="text-xs text-[var(--text-tertiary)]">Move HEAD here. Changes after this commit stay staged.</div>
                </div>
            </label>
            
            <label class="flex items-start gap-3 p-3 rounded border cursor-pointer transition-colors"
                :class="$wire.resetMode === 'mixed' ? 'border-[#084CCF] bg-[rgba(8,76,207,0.05)]' : 'border-[var(--border-default)] hover:border-[var(--border-strong)]'">
                <input type="radio" wire:model.live="resetMode" value="mixed" class="mt-0.5">
                <div>
                    <div class="font-medium text-sm text-[var(--text-primary)]">Mixed Reset</div>
                    <div class="text-xs text-[var(--text-tertiary)]">Move HEAD here. Changes after this commit become unstaged.</div>
                </div>
            </label>
            
            <label class="flex items-start gap-3 p-3 rounded border cursor-pointer transition-colors"
                :class="$wire.resetMode === 'hard' ? 'border-[var(--color-red)] bg-[rgba(210,15,57,0.05)]' : 'border-[var(--border-default)] hover:border-[var(--border-strong)]'">
                <input type="radio" wire:model.live="resetMode" value="hard" class="mt-0.5">
                <div>
                    <div class="font-medium text-sm text-[var(--color-red)]">Hard Reset</div>
                    <div class="text-xs text-[var(--text-tertiary)]">Move HEAD here. All changes after this commit will be PERMANENTLY LOST.</div>
                </div>
            </label>
        </div>
        
        @if($resetMode === 'hard')
            <div class="p-3 bg-[rgba(210,15,57,0.1)] rounded border border-[var(--color-red)]">
                <p class="text-sm text-[var(--color-red)] font-medium mb-2">All changes after this commit will be PERMANENTLY LOST.</p>
                <flux:input wire:model.live="hardResetConfirmText" placeholder='Type "DISCARD" to confirm' />
            </div>
        @endif
        
        @if($targetCommitPushed)
            <div class="text-sm text-[var(--color-yellow)] font-medium">⚠ This commit is pushed. Resetting will require a force push.</div>
        @endif
        
        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="$set('showResetModal', false)">Cancel</flux:button>
            <flux:button 
                variant="{{ $resetMode === 'hard' ? 'danger' : 'primary' }}" 
                wire:click="confirmReset"
                :disabled="$resetMode === 'hard' && $hardResetConfirmText !== 'DISCARD'"
            >
                Reset
            </flux:button>
        </div>
    </flux:modal>

    {{-- Revert Modal --}}
    <flux:modal wire:model="showRevertModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Revert Commit</flux:heading>
            <flux:subheading>This will create a new commit that undoes the changes from this commit.</flux:subheading>
            <div class="font-mono text-xs mt-2 text-[var(--text-secondary)]">{{ $resetTargetSha ? substr($resetTargetSha, 0, 8) : '' }} — {{ $resetTargetMessage }}</div>
        </div>
        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="$set('showRevertModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="confirmRevert">Revert</flux:button>
        </div>
    </flux:modal>

    {{-- Cherry-pick Modal --}}
    <flux:modal wire:model="showCherryPickModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Cherry-pick Commit</flux:heading>
            <flux:subheading>This will apply the changes from this commit to your current branch.</flux:subheading>
            <div class="font-mono text-xs mt-2 text-[var(--text-secondary)]">{{ $cherryPickTargetSha ? substr($cherryPickTargetSha, 0, 8) : '' }} — {{ $cherryPickTargetMessage }}</div>
        </div>
        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="$set('showCherryPickModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="confirmCherryPick">Cherry-pick</flux:button>
        </div>
    </flux:modal>
</div>
