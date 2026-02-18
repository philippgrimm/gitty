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
            @php
                $graphNodesBySha = [];
                foreach ($graphData as $node) {
                    $graphNodesBySha[$node->sha] = $node;
                }
                $maxLane = 0;
                foreach ($graphData as $node) {
                    $maxLane = max($maxLane, $node->lane);
                }
                $graphWidth = max(40, ($maxLane + 1) * 20);
                
                $laneColors = [
                    '#18206F',
                    '#6B4BA0',
                    '#267018',
                    '#B04800',
                    '#1A7A7A',
                    '#C41030',
                    '#2080B0',
                    '#8A6410',
                ];
            @endphp

            @foreach($commits as $index => $commit)
                @php
                    $graphNode = $graphNodesBySha[$commit->sha] ?? null;
                    $lane = $graphNode?->lane ?? 0;
                    $parents = $graphNode?->parents ?? [];
                    $laneColor = $laneColors[$lane % count($laneColors)];
                    
                    $nextCommit = $commits[$index + 1] ?? null;
                    $nextGraphNode = $nextCommit ? ($graphNodesBySha[$nextCommit->sha] ?? null) : null;
                @endphp

                <div 
                    wire:key="commit-{{ $commit->sha }}"
                    wire:click="selectCommit('{{ $commit->sha }}')"
                    @contextmenu="showContextMenu('{{ $commit->sha }}', $event)"
                    class="flex cursor-pointer border-b border-[var(--border-subtle)] transition-colors duration-150"
                    :class="$wire.selectedCommitSha === '{{ $commit->sha }}' ? 'bg-[rgba(24,32,111,0.15)]' : 'bg-white dark:bg-[var(--surface-0)] hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)]'"
                >
                    {{-- Graph column --}}
                    @if($showGraph && $graphNode)
                        <div class="shrink-0 py-2" style="width: {{ $graphWidth }}px;">
                            <svg width="{{ $graphWidth }}" height="40" class="overflow-visible">
                                {{-- Draw lines to parents --}}
                                @foreach($parents as $parentSha)
                                    @php
                                        $parentNode = $graphNodesBySha[$parentSha] ?? null;
                                        $parentLane = $parentNode?->lane ?? $lane;
                                        $parentColor = $laneColors[$parentLane % count($laneColors)];
                                        
                                        $startX = ($lane * 20) + 10;
                                        $endX = ($parentLane * 20) + 10;
                                    @endphp
                                    
                                    @if($parentLane === $lane)
                                        {{-- Straight line down --}}
                                        <line 
                                            x1="{{ $startX }}" 
                                            y1="20" 
                                            x2="{{ $startX }}" 
                                            y2="40" 
                                            stroke="{{ $laneColor }}" 
                                            stroke-width="2"
                                        />
                                    @else
                                        {{-- Merge line --}}
                                        <path 
                                            d="M {{ $startX }} 20 Q {{ $startX }} 30, {{ ($startX + $endX) / 2 }} 35 T {{ $endX }} 40" 
                                            stroke="{{ $parentColor }}" 
                                            stroke-width="2" 
                                            fill="none"
                                        />
                                    @endif
                                @endforeach

                                {{-- Draw line from previous commit if it's in the same lane --}}
                                @if($index > 0 && $nextGraphNode && in_array($commit->sha, $nextGraphNode->parents))
                                    <line 
                                        x1="{{ ($lane * 20) + 10 }}" 
                                        y1="0" 
                                        x2="{{ ($lane * 20) + 10 }}" 
                                        y2="20" 
                                        stroke="{{ $laneColor }}" 
                                        stroke-width="2"
                                    />
                                @endif

                                {{-- Draw commit node --}}
                                <circle 
                                    cx="{{ ($lane * 20) + 10 }}" 
                                    cy="20" 
                                    r="4" 
                                    fill="{{ $laneColor }}" 
                                    stroke="white" 
                                    stroke-width="1.5"
                                />
                            </svg>
                        </div>
                    @endif

                    {{-- Commit details --}}
                    <div class="flex-1 px-4 py-2">
                        {{-- Line 1: SHA | Author | Date --}}
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <div class="flex items-center gap-3 min-w-0">
                                <flux:tooltip :content="$commit->sha" delay="500">
                                    <span class="text-xs text-[#18206F] font-mono">{{ $commit->shortSha }}</span>
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
                                            $bgColor = '#B04800';
                                            $textColor = '#ffffff';
                                        } elseif ($isTag) {
                                            $bgColor = '#6B4BA0';
                                            $textColor = '#ffffff';
                                        } elseif ($isRemote) {
                                            $bgColor = '#1A7A7A';
                                            $textColor = '#ffffff';
                                        } else {
                                            $bgColor = '#267018';
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
                :class="$wire.resetMode === 'soft' ? 'border-[#18206F] bg-[rgba(24,32,111,0.05)]' : 'border-[var(--border-default)] hover:border-[var(--border-strong)]'">
                <input type="radio" wire:model.live="resetMode" value="soft" class="mt-0.5">
                <div>
                    <div class="font-medium text-sm text-[var(--text-primary)]">Soft Reset</div>
                    <div class="text-xs text-[var(--text-tertiary)]">Move HEAD here. Changes after this commit stay staged.</div>
                </div>
            </label>
            
            <label class="flex items-start gap-3 p-3 rounded border cursor-pointer transition-colors"
                :class="$wire.resetMode === 'mixed' ? 'border-[#18206F] bg-[rgba(24,32,111,0.05)]' : 'border-[var(--border-default)] hover:border-[var(--border-strong)]'">
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
