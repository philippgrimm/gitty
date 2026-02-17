<div class="h-full flex flex-col bg-white dark:bg-[var(--surface-0)] font-mono"
     x-data="{
         init() {
             const saved = localStorage.getItem('gitty-diff-view-mode');
             if (saved && (saved === 'unified' || saved === 'split')) {
                 $wire.set('diffViewMode', saved);
             }
         }
     }"
     x-effect="localStorage.setItem('gitty-diff-view-mode', $wire.diffViewMode)">
    @if($isEmpty && !$file)
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 opacity-60">{!! file_get_contents(resource_path('svg/empty-states/no-file.svg')) !!}</div>
                <div class="text-[var(--text-tertiary)] uppercase tracking-wider text-sm">No file selected</div>
            </div>
        </div>
    @elseif($isEmpty && $file)
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 opacity-60">{!! file_get_contents(resource_path('svg/empty-states/no-diff.svg')) !!}</div>
                <div class="text-[var(--text-tertiary)] uppercase tracking-wider text-sm">No changes to display</div>
            </div>
        </div>
    @elseif($isLargeFile)
        <div class="border-b border-[var(--border-default)] px-4 h-10 flex items-center bg-white dark:bg-[var(--surface-0)]">
            <div class="flex items-center gap-3 flex-1 overflow-hidden">
                <flux:tooltip :content="$file" class="min-w-0 flex-1">
                    <span class="text-[var(--text-primary)] text-sm truncate block">{{ $file }}</span>
                </flux:tooltip>
                <div class="flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium uppercase tracking-wider shrink-0 whitespace-nowrap" style="background-color: #fe640b15; color: #fe640b">
                    LARGE FILE
                </div>
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 opacity-60">{!! file_get_contents(resource_path('svg/empty-states/large-file.svg')) !!}</div>
                <div class="text-[var(--text-tertiary)] uppercase tracking-wider text-sm">File too large (&gt;1MB) — diff skipped</div>
            </div>
        </div>
    @elseif($isBinary)
        <div class="border-b border-[var(--border-default)] px-4 h-10 flex items-center bg-white dark:bg-[var(--surface-0)]">
            <div class="flex items-center gap-3 flex-1 overflow-hidden">
                <flux:tooltip :content="$file" class="min-w-0 flex-1">
                    <span class="text-[var(--text-primary)] text-sm truncate block">{{ $file }}</span>
                </flux:tooltip>
                <div class="flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium uppercase tracking-wider shrink-0 whitespace-nowrap" style="background-color: #9ca0b015; color: #9ca0b0">
                    BINARY
                </div>
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 opacity-60">{!! file_get_contents(resource_path('svg/empty-states/binary-file.svg')) !!}</div>
                <div class="text-[var(--text-tertiary)] uppercase tracking-wider text-sm">Binary file — cannot display diff</div>
            </div>
        </div>
    @else
        <div class="border-b border-[var(--border-default)] px-4 h-10 flex items-center bg-white dark:bg-[var(--surface-0)] sticky top-0 z-10" style="box-shadow: var(--shadow-sm)">
            <div class="flex items-center justify-between gap-3 flex-1 overflow-hidden">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <flux:tooltip :content="$file" class="min-w-0 flex-1">
                        <span class="text-[var(--text-primary)] text-sm truncate block">{{ $file }}</span>
                    </flux:tooltip>
                    @if($diffData)
                        @php
                            $badgeColor = match(strtoupper($diffData['status'])) {
                                'MODIFIED', 'M' => '#df8e1d',
                                'ADDED', 'A' => '#40a02b',
                                'DELETED', 'D' => '#d20f39',
                                'RENAMED', 'R' => '#084CCF',
                                default => '#9ca0b0',
                            };
                        @endphp
                        <div class="flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium uppercase tracking-wider shrink-0 whitespace-nowrap" style="background-color: {{ $badgeColor }}15; color: {{ $badgeColor }}">
                            {{ strtoupper($diffData['status']) }}
                        </div>
                    @endif
                </div>
                @if($diffData)
                    <div class="flex items-center gap-4 text-sm shrink-0">
                        <span class="text-[var(--color-green)] font-bold">+{{ $diffData['additions'] }}</span>
                        <span class="text-[var(--color-red)] font-bold">-{{ $diffData['deletions'] }}</span>
                        <flux:tooltip :content="$diffViewMode === 'unified' ? 'Switch to split view' : 'Switch to unified view'">
                            <flux:button wire:click="toggleDiffViewMode" variant="ghost" size="xs" square 
                                class="flex items-center justify-center">
                                @if($diffViewMode === 'unified')
                                    <x-phosphor-columns-light class="w-3.5 h-3.5" />
                                @else
                                    <x-phosphor-rows-light class="w-3.5 h-3.5" />
                                @endif
                            </flux:button>
                        </flux:tooltip>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex-1 overflow-auto">
            <div class="diff-container">
                @if($files)
                    @foreach($files as $fileIndex => $diffFile)
                        <div class="diff-file" data-language="{{ $diffFile['language'] ?? 'text' }}">
                            @if($diffFile['isBinary'])
                                <div class="diff-binary">Binary file</div>
                            @else
                                @if($diffViewMode === 'split')
                                    {{-- Split View --}}
                                    @foreach($diffFile['hunks'] as $hunkIndex => $hunk)
                                        <div class="diff-hunk" wire:key="split-hunk-{{ $fileIndex }}-{{ $hunkIndex }}">
                                            <div class="diff-hunk-header group">
                                                <span class="flex-1">{{ $hunk['header'] }}</span>
                                                @if($isStaged)
                                                    <button wire:click="unstageHunk({{ $fileIndex }}, {{ $hunkIndex }})"
                                                        class="hunk-action-btn opacity-0 group-hover:opacity-100 transition-opacity duration-200 px-3 py-1 text-xs font-bold uppercase tracking-wider bg-red-900/50 hover:bg-red-900 text-red-100 border border-red-700 rounded"
                                                        title="Unstage this hunk">
                                                        − Unstage
                                                    </button>
                                                @else
                                                    <button wire:click="stageHunk({{ $fileIndex }}, {{ $hunkIndex }})"
                                                        class="hunk-action-btn opacity-0 group-hover:opacity-100 transition-opacity duration-200 px-3 py-1 text-xs font-bold uppercase tracking-wider bg-green-900/50 hover:bg-green-900 text-green-100 border border-green-700 rounded"
                                                        title="Stage this hunk">
                                                        + Stage
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="flex" x-data="{
                                                syncScroll(source, target) {
                                                    target.scrollTop = source.scrollTop;
                                                }
                                            }">
                                                {{-- Left: Old file (deletions + context) --}}
                                                <div class="w-1/2 border-r border-[var(--border-default)] overflow-y-auto"
                                                     x-ref="leftPane{{ $hunkIndex }}"
                                                     @scroll="syncScroll($refs.leftPane{{ $hunkIndex }}, $refs.rightPane{{ $hunkIndex }})">
                                                    @foreach($this->getSplitLines($hunk) as $pair)
                                                        @if($pair['left'])
                                                            <div class="{{ $pair['left']['type'] === 'deletion' ? 'diff-line-deletion' : 'diff-line-context' }} flex">
                                                                <span class="line-number w-12 text-right pr-2 text-xs select-none shrink-0 border-r border-[var(--border-subtle)]">{{ $pair['left']['oldLineNumber'] ?? '' }}</span>
                                                                <span class="line-content flex-1 font-mono text-sm whitespace-pre-wrap break-all px-4 py-1">{{ $pair['left']['content'] }}</span>
                                                            </div>
                                                        @else
                                                            <div class="diff-line-context flex opacity-0 pointer-events-none">
                                                                <span class="line-number w-12 text-right pr-2 text-xs select-none shrink-0 border-r border-[var(--border-subtle)]">&nbsp;</span>
                                                                <span class="line-content flex-1 font-mono text-sm px-4 py-1">&nbsp;</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>

                                                {{-- Right: New file (additions + context) --}}
                                                <div class="w-1/2 overflow-y-auto"
                                                     x-ref="rightPane{{ $hunkIndex }}"
                                                     @scroll="syncScroll($refs.rightPane{{ $hunkIndex }}, $refs.leftPane{{ $hunkIndex }})">
                                                    @foreach($this->getSplitLines($hunk) as $pair)
                                                        @if($pair['right'])
                                                            <div class="{{ $pair['right']['type'] === 'addition' ? 'diff-line-addition' : 'diff-line-context' }} flex">
                                                                <span class="line-number w-12 text-right pr-2 text-xs select-none shrink-0 border-r border-[var(--border-subtle)]">{{ $pair['right']['newLineNumber'] ?? '' }}</span>
                                                                <span class="line-content flex-1 font-mono text-sm whitespace-pre-wrap break-all px-4 py-1">{{ $pair['right']['content'] }}</span>
                                                            </div>
                                                        @else
                                                            <div class="diff-line-context flex opacity-0 pointer-events-none">
                                                                <span class="line-number w-12 text-right pr-2 text-xs select-none shrink-0 border-r border-[var(--border-subtle)]">&nbsp;</span>
                                                                <span class="line-content flex-1 font-mono text-sm px-4 py-1">&nbsp;</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    {{-- Unified View --}}
                                    @foreach($diffFile['hunks'] as $hunkIndex => $hunk)
                                        <div class="diff-hunk" wire:key="hunk-{{ $fileIndex }}-{{ $hunkIndex }}"
                                             x-data="{
                                                 selected: new Set(),
                                                 lastClicked: null,
                                                 toggle(idx, type) {
                                                     if (type === 'context') return;
                                                     this.selected.has(idx) ? this.selected.delete(idx) : this.selected.add(idx);
                                                     this.lastClicked = idx;
                                                     this.selected = new Set(this.selected);
                                                 },
                                                 rangeSelect(idx, type) {
                                                     if (type === 'context' || this.lastClicked === null) return;
                                                     const [from, to] = [Math.min(this.lastClicked, idx), Math.max(this.lastClicked, idx)];
                                                     for (let i = from; i <= to; i++) {
                                                         const lineType = this.$refs['line-' + i]?.dataset?.lineType;
                                                         if (lineType && lineType !== 'context') {
                                                             this.selected.add(i);
                                                         }
                                                     }
                                                     this.selected = new Set(this.selected);
                                                 },
                                                 isSelected(idx) { return this.selected.has(idx); },
                                                 hasSelection() { return this.selected.size > 0; },
                                                 getSelected() { return [...this.selected]; },
                                                 clear() { this.selected = new Set(); this.lastClicked = null; }
                                             }">
                                            <div class="diff-hunk-header group">
                                                <span class="flex-1">{{ $hunk['header'] }}</span>
                                                @if($isStaged)
                                                    <button wire:click="unstageHunk({{ $fileIndex }}, {{ $hunkIndex }})"
                                                        class="hunk-action-btn opacity-0 group-hover:opacity-100 transition-opacity duration-200 px-3 py-1 text-xs font-bold uppercase tracking-wider bg-red-900/50 hover:bg-red-900 text-red-100 border border-red-700 rounded"
                                                        title="Unstage this hunk">
                                                        − Unstage
                                                    </button>
                                                @else
                                                    <button wire:click="stageHunk({{ $fileIndex }}, {{ $hunkIndex }})"
                                                        class="hunk-action-btn opacity-0 group-hover:opacity-100 transition-opacity duration-200 px-3 py-1 text-xs font-bold uppercase tracking-wider bg-green-900/50 hover:bg-green-900 text-green-100 border border-green-700 rounded"
                                                        title="Stage this hunk">
                                                        + Stage
                                                    </button>
                                                @endif
                                            </div>

                                            @foreach($hunk['lines'] as $lineIndex => $line)
                                                <div class="{{ match($line['type']) { 'addition' => 'diff-line-addition', 'deletion' => 'diff-line-deletion', default => 'diff-line-context' } }}"
                                                     x-ref="line-{{ $lineIndex }}"
                                                     data-line-type="{{ $line['type'] }}"
                                                     @click="toggle({{ $lineIndex }}, '{{ $line['type'] }}')"
                                                     @click.shift="rangeSelect({{ $lineIndex }}, '{{ $line['type'] }}')"
                                                     :class="{ 'border-l-2 border-[#084CCF]': isSelected({{ $lineIndex }}) }"
                                                     style="cursor: {{ $line['type'] !== 'context' ? 'pointer' : 'default' }}">
                                                    <span class="line-number">{{ $line['oldLineNumber'] ?? '' }}</span>
                                                    <span class="line-number">{{ $line['newLineNumber'] ?? '' }}</span>
                                                    <span class="line-content">{{ $line['content'] }}</span>
                                                </div>
                                            @endforeach

                                            <div x-show="hasSelection()" x-transition
                                                 class="flex items-center gap-2 px-4 py-1.5 bg-[var(--surface-1)] border-t border-[var(--border-default)]">
                                                <span class="text-xs text-[var(--text-tertiary)] font-mono" x-text="selected.size + ' lines selected'"></span>
                                                <div class="flex-1"></div>
                                                @if($isStaged)
                                                    <flux:button size="xs" variant="ghost"
                                                        @click="$wire.unstageSelectedLines({{ $fileIndex }}, {{ $hunkIndex }}, getSelected()).then(() => clear())">
                                                        Unstage Lines
                                                    </flux:button>
                                                @else
                                                    <flux:button size="xs" variant="primary"
                                                        @click="$wire.stageSelectedLines({{ $fileIndex }}, {{ $hunkIndex }}, getSelected()).then(() => clear())">
                                                        Stage Lines
                                                    </flux:button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endif
</div>
