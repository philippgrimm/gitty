<div 
    wire:poll.15s.visible="refreshBranches"
    class="flex items-center gap-2 font-mono"
>
    @if($isDetachedHead)
        <div class="flex items-center gap-2 px-3 py-1.5 bg-[#fe640b]/10 border border-[#fe640b]/30 rounded text-[#fe640b]">
            <span class="text-xs uppercase tracking-wider font-semibold">HEAD detached at {{ substr($currentBranch, 0, 7) }}</span>
            <flux:button 
                @click="$wire.showCreateModal = true"
                variant="ghost" 
                size="xs"
                class="text-xs uppercase tracking-wider"
            >
                Create branch here
            </flux:button>
        </div>
    @else
        <flux:dropdown position="bottom-start">
            <flux:button 
                variant="subtle" 
                size="xs"
                class="flex items-center gap-2 px-2.5 py-1 !bg-[#eff1f5] border border-[#ccd0da] hover:border-[#bcc0cc] transition-colors text-sm rounded-lg"
            >
                <x-phosphor-git-branch-light class="w-3.5 h-3.5 text-[#6c6f85] shrink-0" />
                <span class="font-semibold text-[#4c4f69]">{{ $currentBranch }}</span>
                @if($aheadBehind['ahead'] > 0 || $aheadBehind['behind'] > 0)
                    <div class="flex items-center gap-1">
                        @if($aheadBehind['ahead'] > 0)
                            <span class="font-mono text-xs text-[#40a02b]">↑{{ $aheadBehind['ahead'] }}</span>
                        @endif
                        @if($aheadBehind['behind'] > 0)
                            <span class="font-mono text-xs text-[#d20f39]">↓{{ $aheadBehind['behind'] }}</span>
                        @endif
                    </div>
                @endif
                <svg class="w-3 h-3 text-[#6c6f85]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </flux:button>

            <flux:menu class="w-96 max-h-[600px] overflow-hidden !p-0">
                <div
                    class="flex flex-col h-full"
                    x-data="{
                        activeIndex: -1,
                        items: [],
                        init() {
                            this.updateItems();
                        },
                        updateItems() {
                            this.items = [...this.$el.querySelectorAll('[data-branch-item]')];
                            this.activeIndex = -1;
                        },
                        navigate(direction) {
                            if (this.items.length === 0) return;
                            if (direction === 'down') {
                                this.activeIndex = this.activeIndex < this.items.length - 1 ? this.activeIndex + 1 : 0;
                            } else {
                                this.activeIndex = this.activeIndex > 0 ? this.activeIndex - 1 : this.items.length - 1;
                            }
                            this.items[this.activeIndex]?.scrollIntoView({ block: 'nearest' });
                        },
                        selectActive() {
                            if (this.activeIndex >= 0 && this.items[this.activeIndex]) {
                                this.items[this.activeIndex].click();
                            }
                        },
                        contextMenu: { show: false, branch: '', x: 0, y: 0 },
                        showBranchContextMenu(branchName, event) {
                            event.preventDefault();
                            this.contextMenu = { show: true, branch: branchName, x: event.clientX, y: event.clientY };
                        },
                        hideBranchContextMenu() { this.contextMenu.show = false; },
                    }"
                    @keydown.arrow-down.prevent="navigate('down')"
                    @keydown.arrow-up.prevent="navigate('up')"
                    @keydown.enter.prevent="selectActive()"
                >
                    {{-- Search field --}}
                    <div class="p-2 border-b border-[var(--border-subtle)] sticky top-0 z-10 bg-white">
                        <div class="flex items-center gap-1.5 px-2 py-1 border border-[var(--border-subtle)] rounded">
                            <x-phosphor-magnifying-glass-light class="w-3 h-3 text-[var(--text-tertiary)] shrink-0" />
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="branchQuery"
                                placeholder="Search…"
                                class="w-full bg-transparent border-none outline-none text-[11px] text-[var(--text-primary)] placeholder-[var(--text-tertiary)] font-mono p-0 focus:ring-0"
                                @keydown.arrow-down.prevent="$event.target.blur(); navigate('down')"
                            />
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        {{-- Local Branches header --}}
                        <div class="px-3 py-1.5 border-b border-[var(--border-subtle)]">
                            <span class="text-[10px] uppercase tracking-wider font-medium text-[var(--text-tertiary)]">Local Branches</span>
                        </div>

                        @forelse($this->filteredLocalBranches as $branch)
                            <div
                                class="group flex items-center justify-between px-3 py-1.5 transition-colors cursor-pointer"
                                :class="activeIndex === {{ $loop->index }} ? 'bg-[#eff1f5]' : 'hover:bg-[#eff1f5]'"
                                data-branch-item
                                wire:click="switchBranch('{{ $branch['name'] }}')"
                                x-on:click="$el.closest('[popover]')?.hidePopover()"
                                @if(!$branch['isCurrent'])
                                    x-on:contextmenu="showBranchContextMenu('{{ $branch['name'] }}', $event)"
                                @endif
                            >
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <div class="w-4 flex items-center justify-center shrink-0">
                                        @if($branch['isCurrent'])
                                            <div class="w-1.5 h-1.5 rounded-full bg-[var(--accent)]"></div>
                                        @endif
                                    </div>
                                    <span class="text-sm truncate {{ $branch['isCurrent'] ? 'font-semibold text-[var(--text-primary)]' : 'text-[var(--text-secondary)]' }}">
                                        {{ $branch['name'] }}
                                    </span>
                                </div>

                                @if(!$branch['isCurrent'])
                                    <button
                                        wire:click.stop="deleteBranch('{{ $branch['name'] }}')"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity text-[var(--text-tertiary)] hover:text-[var(--color-red)] p-0.5 shrink-0"
                                    >
                                        <x-phosphor-trash-light class="w-3.5 h-3.5" />
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="px-3 py-4 text-center text-[var(--text-tertiary)] text-sm">No local branches found</div>
                        @endforelse

                        @if($this->filteredRemoteBranches->isNotEmpty())
                            {{-- Remote Branches header --}}
                            <div class="px-3 py-1.5 border-t border-b border-[var(--border-subtle)]">
                                <span class="text-[10px] uppercase tracking-wider font-medium text-[var(--text-tertiary)]">Remote Branches</span>
                            </div>

                            @foreach($this->filteredRemoteBranches as $branch)
                                @php
                                    $cleanName = str_replace('remotes/', '', $branch['name']);
                                    $remoteIndex = $loop->index + $this->filteredLocalBranches->count();
                                @endphp
                                <div
                                    class="flex items-center px-3 py-1.5 transition-colors cursor-default"
                                    :class="activeIndex === {{ $remoteIndex }} ? 'bg-[#eff1f5]' : 'hover:bg-[#eff1f5]'"
                                    data-branch-item
                                >
                                    <div class="w-4 shrink-0"></div>
                                    <span class="text-sm truncate text-[var(--text-tertiary)] italic ml-2">
                                        {{ $cleanName }}
                                    </span>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    {{-- Right-click context menu for branches --}}
                    <template x-if="contextMenu.show">
                        <div
                            @click.outside="hideBranchContextMenu()"
                            @keydown.escape.window="hideBranchContextMenu()"
                            @scroll.window="hideBranchContextMenu()"
                            :style="`position: fixed; left: ${contextMenu.x}px; top: ${contextMenu.y}px; z-index: 50;`"
                            class="bg-white border border-[#ccd0da] rounded-lg shadow-lg py-1 min-w-[200px] font-mono text-sm"
                        >
                            <button
                                @click="$wire.switchBranch(contextMenu.branch); hideBranchContextMenu(); $el.closest('[popover]')?.hidePopover()"
                                class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#4c4f69]"
                            >
                                Switch to Branch
                            </button>
                            <button
                                @click="$wire.mergeBranch(contextMenu.branch); hideBranchContextMenu(); $el.closest('[popover]')?.hidePopover()"
                                class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#4c4f69]"
                            >
                                Merge into {{ $currentBranch }}
                            </button>
                            <div class="border-t border-[#dce0e8] my-1"></div>
                            <button
                                @click="$wire.deleteBranch(contextMenu.branch); hideBranchContextMenu(); $el.closest('[popover]')?.hidePopover()"
                                class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#d20f39]"
                            >
                                Delete Branch
                            </button>
                        </div>
                    </template>

                    {{-- New Branch button --}}
                    <div class="border-t border-[var(--border-subtle)] p-2 sticky bottom-0 bg-white">
                        <button
                            @click="$wire.showCreateModal = true"
                            type="button"
                            class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs uppercase tracking-wider text-[var(--text-secondary)] hover:bg-[#eff1f5] transition-colors rounded"
                        >
                            <x-phosphor-plus-light class="w-3.5 h-3.5 shrink-0" />
                            <span>New Branch</span>
                        </button>
                    </div>
                </div>
            </flux:menu>
        </flux:dropdown>
    @endif

    <flux:modal wire:model="showCreateModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Create New Branch</flux:heading>
            <flux:subheading class="font-mono">
                Create a new branch from an existing branch
            </flux:subheading>
        </div>

        <flux:input 
            wire:model="newBranchName"
            label="Branch name"
            placeholder="feature/my-feature"
            class="font-mono"
        />

        <div>
            <flux:field>
                <flux:label class="font-mono">Base branch</flux:label>
                <flux:select wire:model="baseBranch">
                    @foreach($this->filteredLocalBranches as $branch)
                        <flux:select.option value="{{ $branch['name'] }}">{{ $branch['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">Cancel</flux:button>
            <flux:button 
                variant="primary" 
                wire:click="createBranch"
                :disabled="empty(trim($newBranchName))"
                class="uppercase tracking-wider"
            >
                Create Branch
            </flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showAutoStashModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Stash & Switch?</flux:heading>
            <flux:subheading class="font-mono">
                You have uncommitted changes that conflict with <span class="text-[var(--text-primary)] font-bold">{{ $autoStashTargetBranch }}</span>. Stash them and switch?
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="cancelAutoStash">Cancel</flux:button>
            <flux:button 
                variant="primary" 
                wire:click="confirmAutoStash"
                class="uppercase tracking-wider"
            >
                Stash & Switch
            </flux:button>
        </div>
    </flux:modal>
</div>
