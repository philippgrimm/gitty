<div
    wire:poll.30s.visible="handleStatusUpdated"
    x-data="{
        contextMenu: { show: false, x: 0, y: 0, targetSha: null },
        commitContext: @js($commitContextData),

        showContextMenu(sha, event) {
            event.preventDefault();
            this.contextMenu = { show: true, x: event.clientX, y: event.clientY, targetSha: sha };
        },

        hideContextMenu() {
            this.contextMenu.show = false;
        },

        copyToClipboard(text) {
            if (!text) return;
            navigator.clipboard.writeText(text);
            this.hideContextMenu();
        },

        getCommitMessage(sha) {
            return this.commitContext?.[sha]?.message ?? '';
        },

        scrollToCommit(sha) {
            if (!sha) return;

            this.$nextTick(() => {
                const target = this.$el.querySelector(`[data-history-sha='${sha}']`);
                if (target) {
                    target.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            });
        },
    }"
    x-effect="commitContext = @js($commitContextData)"
    @scroll-history-to-commit.window="scrollToCommit($event.detail.sha)"
    class="h-full flex flex-col bg-white dark:bg-[var(--surface-0)] text-[var(--text-primary)] font-display crt-scanlines relative"
>
    @if($rows->isEmpty())
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center space-y-3">
                <div class="w-20 h-20 mx-auto opacity-60">
                    <x-pixelarticons-clock class="w-full h-full" />
                </div>
                <div class="text-[var(--text-tertiary)] uppercase tracking-wider text-xs font-medium">No commits yet</div>
            </div>
        </div>
    @else
        <div class="sticky top-0 z-10 bg-[var(--surface-1)] border-b border-[var(--border-default)] px-4 h-10 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <x-pixelarticons-clock class="w-4 h-4 text-[var(--text-tertiary)]" />
                <div class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)] font-display">History</div>
                <span class="text-xs text-[var(--text-tertiary)] font-mono">{{ $commitsCount }}</span>
            </div>

            <div class="flex items-center gap-1.5">
                <flux:button.group>
                    <flux:button
                        wire:click="setHistoryScope('current')"
                        variant="{{ $historyScope === 'current' ? 'primary' : 'subtle' }}"
                        size="xs"
                    >
                        Current
                    </flux:button>
                    <flux:button
                        wire:click="setHistoryScope('all')"
                        variant="{{ $historyScope === 'all' ? 'primary' : 'subtle' }}"
                        size="xs"
                    >
                        All
                    </flux:button>
                </flux:button.group>

                <flux:tooltip content="Close History (⌘H)">
                    <flux:button
                        @click="$dispatch('toggle-history-panel')"
                        variant="ghost"
                        size="xs"
                        square
                        class="text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]"
                    >
                        <x-pixelarticons-close class="w-4 h-4" />
                    </flux:button>
                </flux:tooltip>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto relative">
            @if($graphSvg['height'] > 0)
                <svg
                    class="absolute top-0 left-0 pointer-events-none z-[1]"
                    width="{{ $graphSvg['width'] }}"
                    height="{{ $graphSvg['height'] }}"
                    style="shape-rendering: geometricPrecision;"
                >
                    <defs>
                        <filter id="neon-glow" filterUnits="userSpaceOnUse" x="-8" y="0" width="{{ $graphSvg['width'] + 16 }}" height="{{ $graphSvg['height'] }}">
                            <feGaussianBlur stdDeviation="3" result="blur"/>
                            <feMerge>
                                <feMergeNode in="blur"/>
                                <feMergeNode in="blur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                        <filter id="neon-glow-dot" x="-80%" y="-80%" width="260%" height="260%">
                            <feGaussianBlur stdDeviation="2" result="blur"/>
                            <feMerge>
                                <feMergeNode in="blur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                    </defs>
                    @foreach($graphSvg['paths'] as $path)
                        <path d="{{ $path['d'] }}" class="graph-halo" stroke-width="5"/>
                    @endforeach
                    @foreach($graphSvg['paths'] as $path)
                        <path d="{{ $path['d'] }}" class="graph-lane graph-lane-{{ $path['laneIndex'] }}" stroke-width="2"/>
                    @endforeach
                    @foreach($graphSvg['circles'] as $c)
                        @if($c['type'] === 'regular')
                            <circle cx="{{ $c['cx'] }}" cy="{{ $c['cy'] }}" r="{{ $c['r'] }}" class="graph-dot graph-dot-{{ $c['laneIndex'] }}" stroke-width="{{ $c['strokeWidth'] }}"/>
                        @else
                            <circle cx="{{ $c['cx'] }}" cy="{{ $c['cy'] }}" r="{{ $c['r'] }}" class="graph-dot-outline graph-dot-outline-{{ $c['laneIndex'] }}" stroke-width="{{ $c['strokeWidth'] }}"/>
                        @endif
                    @endforeach
                </svg>
            @endif

            @foreach($rows as $row)
                <div
                    wire:key="history-row-{{ $row->sha }}"
                    data-history-sha="{{ $row->sha }}"
                    wire:click="selectCommit('{{ $row->sha }}')"
                    @contextmenu="showContextMenu('{{ $row->sha }}', $event)"
                    class="border-b border-[var(--border-subtle)] cursor-pointer transition-colors duration-100"
                    style="height: {{ $graphSvg['rowHeight'] }}px; box-sizing: border-box;"
                    :class="$wire.selectedCommitSha === '{{ $row->sha }}' ? 'bg-[var(--accent-muted)]' : 'bg-white dark:bg-[var(--surface-0)] hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)]'"
                >
                    <flux:tooltip :content="$row->shortSha . ' · ' . $row->author . ' · ' . $row->date . ' — ' . $row->message" delay="400">
                        <div
                            class="h-full flex items-center gap-2 pr-3"
                            style="padding-left: {{ $graphSvg['width'] + 12 }}px;"
                        >
                            <span class="text-xs text-[var(--text-primary)] truncate flex-1 min-w-0 font-mono">{{ $row->message }}</span>

                            @if(!empty($row->refs))
                                @foreach($row->refs as $ref)
                                    @php
                                        $isHead = str_contains($ref, 'HEAD');
                                        $isRemote = str_contains($ref, '/') && ! str_contains($ref, 'tag:');
                                        $isTag = str_contains($ref, 'tag:');

                                        $displayRef = str_replace('HEAD -> ', '', $ref);
                                        $displayRef = str_replace('tag: ', '', $displayRef);

                                        $badgeClass = match(true) {
                                            $isHead && ! str_contains($ref, '->') => 'ref-badge-head',
                                            $isTag => 'ref-badge-tag',
                                            $isRemote => 'ref-badge-remote',
                                            default => 'ref-badge-branch',
                                        };
                                    @endphp
                                    <span class="px-1 py-0 rounded text-[10px] font-medium shrink-0 leading-tight {{ $badgeClass }}">
                                        {{ $displayRef }}
                                    </span>
                                @endforeach
                            @endif

                            @if($row->author !== '')
                                <span class="text-[11px] text-[var(--text-tertiary)] shrink-0 max-w-[100px] truncate">{{ $row->author }}</span>
                            @endif

                            @if($row->date !== '')
                                <span class="text-[11px] text-[var(--text-tertiary)] shrink-0 tabular-nums">{{ $row->date }}</span>
                            @endif
                        </div>
                    </flux:tooltip>
                </div>
            @endforeach

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

    <div
        x-show="contextMenu.show"
        x-cloak
        @click.outside="hideContextMenu()"
        @keydown.escape.window="hideContextMenu()"
        @scroll.window="hideContextMenu()"
        :style="`position: fixed; left: ${contextMenu.x}px; top: ${contextMenu.y}px; z-index: 50;`"
        class="bg-white dark:bg-[var(--surface-0)] border border-[var(--border-default)] rounded-lg shadow-lg py-1 min-w-[190px] font-mono text-sm terminal-dropdown"
    >
        <button
            @click="copyToClipboard(contextMenu.targetSha)"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)]"
        >
            Copy SHA
        </button>
        <button
            @click="copyToClipboard(getCommitMessage(contextMenu.targetSha))"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)]"
        >
            Copy Message
        </button>
        <div class="border-t border-[var(--border-subtle)] my-1"></div>
        <button
            @click="$wire.promptRevert(contextMenu.targetSha, getCommitMessage(contextMenu.targetSha)); hideContextMenu()"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
        >
            <x-pixelarticons-undo class="w-4 h-4" />
            <span>Revert this Commit</span>
        </button>
        <button
            @click="$wire.promptCherryPick(contextMenu.targetSha, getCommitMessage(contextMenu.targetSha)); hideContextMenu()"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
        >
            <x-pixelarticons-git-commit class="w-4 h-4" />
            <span>Cherry-pick this Commit</span>
        </button>
        <button
            @click="$wire.promptReset(contextMenu.targetSha, getCommitMessage(contextMenu.targetSha)); hideContextMenu()"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
        >
            <x-pixelarticons-reply class="w-4 h-4" />
            <span>Reset to this Commit</span>
        </button>
        <div class="border-t border-[var(--border-subtle)] my-1"></div>
        <button
            @click="$wire.promptInteractiveRebase(contextMenu.targetSha); hideContextMenu()"
            class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
        >
            <x-pixelarticons-git-merge class="w-4 h-4" />
            <span>Interactive Rebase</span>
        </button>
    </div>

    <flux:modal wire:model="showResetModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Reset to Commit</flux:heading>
            <flux:subheading class="font-mono text-xs">{{ $resetTargetSha ? substr($resetTargetSha, 0, 8) : '' }} — {{ $resetTargetMessage }}</flux:subheading>
        </div>

        <div class="space-y-3">
            <label class="flex items-start gap-3 p-3 rounded border cursor-pointer transition-colors"
                :class="$wire.resetMode === 'soft' ? 'border-[#4040B0] bg-[rgba(64,64,176,0.05)]' : 'border-[var(--border-default)] hover:border-[var(--border-strong)]'">
                <input type="radio" wire:model.live="resetMode" value="soft" class="mt-0.5">
                <div>
                    <div class="font-medium text-sm text-[var(--text-primary)]">Soft Reset</div>
                    <div class="text-xs text-[var(--text-tertiary)]">Move HEAD here. Changes after this commit stay staged.</div>
                </div>
            </label>

            <label class="flex items-start gap-3 p-3 rounded border cursor-pointer transition-colors"
                :class="$wire.resetMode === 'mixed' ? 'border-[#4040B0] bg-[rgba(64,64,176,0.05)]' : 'border-[var(--border-default)] hover:border-[var(--border-strong)]'">
                <input type="radio" wire:model.live="resetMode" value="mixed" class="mt-0.5">
                <div>
                    <div class="font-medium text-sm text-[var(--text-primary)]">Mixed Reset</div>
                    <div class="text-xs text-[var(--text-tertiary)]">Move HEAD here. Changes after this commit become unstaged.</div>
                </div>
            </label>

            <label class="flex items-start gap-3 p-3 rounded border cursor-pointer transition-colors"
                :class="$wire.resetMode === 'hard' ? 'border-[var(--color-red)] bg-[rgba(217,20,64,0.05)]' : 'border-[var(--border-default)] hover:border-[var(--border-strong)]'">
                <input type="radio" wire:model.live="resetMode" value="hard" class="mt-0.5">
                <div>
                    <div class="font-medium text-sm text-[var(--color-red)]">Hard Reset</div>
                    <div class="text-xs text-[var(--text-tertiary)]">Move HEAD here. All changes after this commit will be PERMANENTLY LOST.</div>
                </div>
            </label>
        </div>

        @if($resetMode === 'hard')
            <div class="p-3 bg-[rgba(217,20,64,0.1)] rounded border border-[var(--color-red)]">
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
