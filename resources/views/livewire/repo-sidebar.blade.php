<div 
    wire:poll.30s.visible="refreshSidebar"
    x-data="{ 
        branchesOpen: false, 
        remotesOpen: false, 
        tagsOpen: false, 
        stashesOpen: false,
        showDropModal: false,
        confirmDropIndex: null,
        showDeleteTagModal: false,
        confirmDeleteTag: null,
        stashMenu: { show: false, x: 0, y: 0, index: null },
        tagMenu: { show: false, x: 0, y: 0, name: null },
        
        openStashMenu(index, event) {
            this.stashMenu = { 
                show: true, 
                x: event.clientX, 
                y: event.clientY, 
                index: index 
            };
        },
        
        hideStashMenu() { 
            this.stashMenu.show = false; 
        },
        
        openTagMenu(name, event) {
            this.tagMenu = { 
                show: true, 
                x: event.clientX, 
                y: event.clientY, 
                name: name 
            };
        },
        
        hideTagMenu() { 
            this.tagMenu.show = false; 
        }
    }"
    class="h-full flex flex-col bg-[var(--surface-0)] text-[var(--text-primary)] font-mono overflow-hidden"
>
    <div class="flex-1 overflow-y-auto">
        <div class="border-b border-[var(--border-default)]">
            <button 
                @click="remotesOpen = !remotesOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-[var(--surface-2)] transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)]">Remotes</div>
                    <span class="text-xs text-[var(--text-tertiary)] font-mono">{{ count($remotes) }}</span>
                </div>
                <div class="text-[var(--text-tertiary)] transition-transform duration-150" :class="{ 'rotate-90': remotesOpen }">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </button>
            <div x-show="remotesOpen" x-collapse class="divide-y divide-[#C8C3B8]">
                @forelse($remotes as $remote)
                    <div class="px-4 py-2.5">
                        <div class="text-sm text-[var(--text-secondary)] font-semibold mb-1">{{ $remote['name'] }}</div>
                        <flux:tooltip :content="$remote['fetchUrl']">
                            <div class="text-xs text-[var(--text-tertiary)] truncate">{{ $remote['fetchUrl'] }}</div>
                        </flux:tooltip>
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-[var(--text-tertiary)] uppercase tracking-wider font-medium">No remotes</div>
                @endforelse
            </div>
        </div>

        <div class="border-b border-[var(--border-default)]">
            <div 
                @click="tagsOpen = !tagsOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-[var(--surface-2)] transition-colors cursor-pointer"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)]">Tags</div>
                    <span class="text-xs text-[var(--text-tertiary)] font-mono">{{ count($tags) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:tooltip content="Create Tag">
                        <button 
                            @click.stop="$wire.set('showCreateTagModal', true)"
                            class="shrink-0 p-0.5 rounded text-[var(--text-tertiary)] hover:text-[var(--text-secondary)] hover:bg-[var(--surface-3)] transition-colors"
                        >
                            <x-phosphor-plus class="w-3.5 h-3.5" />
                        </button>
                    </flux:tooltip>
                    <div class="text-[var(--text-tertiary)] transition-transform duration-150" :class="{ 'rotate-90': tagsOpen }">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </div>
            <div x-show="tagsOpen" x-collapse>
                @forelse($tags as $tag)
                    <div 
                        @contextmenu.prevent="openTagMenu('{{ $tag['name'] }}', $event)"
                        class="px-4 py-2 hover:bg-[var(--surface-2)] transition-colors flex items-center gap-2"
                    >
                        {{-- Tag name (truncated, fills remaining space) --}}
                        <flux:tooltip :content="$tag['name']">
                            <div class="text-sm text-[var(--text-secondary)] truncate flex-1 min-w-0">{{ $tag['name'] }}</div>
                        </flux:tooltip>
                        
                        {{-- SHA --}}
                        @if($tag['sha'])
                            <div class="text-xs text-[var(--text-tertiary)] font-mono shrink-0">{{ substr($tag['sha'], 0, 7) }}</div>
                        @endif
                        
                        {{-- 3-dot menu button (always visible, subtle) --}}
                        <button 
                            @click.stop="openTagMenu('{{ $tag['name'] }}', $event)"
                            class="shrink-0 p-0.5 rounded text-[var(--text-tertiary)] hover:text-[var(--text-secondary)] hover:bg-[var(--surface-3)] transition-colors"
                        >
                            <x-phosphor-dots-three class="w-4 h-4" />
                        </button>
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-[var(--text-tertiary)] uppercase tracking-wider font-medium">No tags</div>
                @endforelse

                {{-- Tag context menu --}}
                <div
                    x-show="tagMenu.show"
                    x-cloak
                    @click.outside="hideTagMenu()"
                    @keydown.escape.window="hideTagMenu()"
                    @scroll.window="hideTagMenu()"
                    :style="`position: fixed; left: ${tagMenu.x}px; top: ${tagMenu.y}px; z-index: 50;`"
                    class="bg-white dark:bg-[var(--surface-0)] border border-[var(--border-default)] rounded-lg shadow-lg py-1 min-w-[140px] font-mono text-sm"
                >
                    <button 
                        @click="$wire.pushTag(tagMenu.name); hideTagMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
                    >
                        <x-phosphor-arrow-up class="w-3.5 h-3.5" />
                        Push to Remote
                    </button>
                    <div class="border-t border-[var(--border-subtle)] my-1"></div>
                    <button 
                        @click="confirmDeleteTag = tagMenu.name; showDeleteTagModal = true; hideTagMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--color-red)] flex items-center gap-2"
                    >
                        <x-phosphor-trash class="w-3.5 h-3.5" />
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <div class="border-b border-[var(--border-default)]">
            <button 
                @click="stashesOpen = !stashesOpen"
                class="w-full px-4 py-3 flex items-center justify-between hover:bg-[var(--surface-2)] transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)]">Stashes</div>
                    <span class="text-xs text-[var(--text-tertiary)] font-mono">{{ count($stashes) }}</span>
                </div>
                <div class="text-[var(--text-tertiary)] transition-transform duration-150" :class="{ 'rotate-90': stashesOpen }">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </button>
            <div x-show="stashesOpen" x-collapse>
                @forelse($stashes as $stash)
                    <div 
                        @contextmenu.prevent="openStashMenu({{ $stash['index'] }}, $event)"
                        class="px-4 py-2 hover:bg-[var(--surface-2)] transition-colors flex items-center gap-2"
                    >
                        {{-- Stash reference --}}
                        <div class="text-xs text-[var(--text-tertiary)] font-mono shrink-0">{{ 'stash@{' . $stash['index'] . '}' }}</div>
                        
                        {{-- Message (truncated, fills remaining space) --}}
                        <div class="text-sm text-[var(--text-secondary)] truncate flex-1 min-w-0">{{ $stash['message'] }}</div>
                        
                        {{-- 3-dot menu button (always visible, subtle) --}}
                        <button 
                            @click.stop="openStashMenu({{ $stash['index'] }}, $event)"
                            class="shrink-0 p-0.5 rounded text-[var(--text-tertiary)] hover:text-[var(--text-secondary)] hover:bg-[var(--surface-3)] transition-colors"
                        >
                            <x-phosphor-dots-three class="w-4 h-4" />
                        </button>
                    </div>
                @empty
                    <div class="px-4 py-3 text-xs text-[var(--text-tertiary)] uppercase tracking-wider font-medium">No stashes</div>
                @endforelse

                {{-- Stash context menu --}}
                <div
                    x-show="stashMenu.show"
                    x-cloak
                    @click.outside="hideStashMenu()"
                    @keydown.escape.window="hideStashMenu()"
                    @scroll.window="hideStashMenu()"
                    :style="`position: fixed; left: ${stashMenu.x}px; top: ${stashMenu.y}px; z-index: 50;`"
                    class="bg-white dark:bg-[var(--surface-0)] border border-[var(--border-default)] rounded-lg shadow-lg py-1 min-w-[140px] font-mono text-sm"
                >
                    <button 
                        @click="$wire.applyStash(stashMenu.index); hideStashMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
                    >
                        <x-phosphor-arrow-counter-clockwise class="w-3.5 h-3.5" />
                        Apply
                    </button>
                    <button 
                        @click="$wire.popStash(stashMenu.index); hideStashMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--text-primary)] flex items-center gap-2"
                    >
                        <x-phosphor-arrow-square-out class="w-3.5 h-3.5" />
                        Pop
                    </button>
                    <div class="border-t border-[var(--border-subtle)] my-1"></div>
                    <button 
                        @click="confirmDropIndex = stashMenu.index; showDropModal = true; hideStashMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-[var(--surface-0)] dark:hover:bg-[var(--surface-3)] text-[var(--color-red)] flex items-center gap-2"
                    >
                        <x-phosphor-trash class="w-3.5 h-3.5" />
                        Drop
                    </button>
                </div>
            </div>
        </div>
    </div>

    <flux:modal x-model="showDropModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Drop Stash?</flux:heading>
            <flux:subheading class="font-mono">
                This will permanently delete the stash. This action cannot be undone.
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" @click="showDropModal = false">Cancel</flux:button>
            <flux:button 
                variant="danger" 
                @click="
                    $wire.dropStash(confirmDropIndex);
                    showDropModal = false;
                    confirmDropIndex = null;
                "
            >
                Drop
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

    <flux:modal wire:model="showCreateTagModal" class="space-y-4">
        <flux:heading size="lg" class="font-mono uppercase tracking-wider">Create Tag</flux:heading>
        <flux:input wire:model="newTagName" label="Tag Name" placeholder="v1.0.0" />
        <flux:input wire:model="newTagMessage" label="Message (optional)" placeholder="Release notes..." />
        <flux:input wire:model="newTagCommit" label="Commit (optional)" placeholder="HEAD" />
        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="$set('showCreateTagModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="createTag">Create</flux:button>
        </div>
    </flux:modal>

    <flux:modal x-model="showDeleteTagModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Delete Tag?</flux:heading>
            <flux:subheading class="font-mono">
                This will permanently delete the tag. This action cannot be undone.
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" @click="showDeleteTagModal = false">Cancel</flux:button>
            <flux:button 
                variant="danger" 
                @click="
                    $wire.deleteTag(confirmDeleteTag);
                    showDeleteTagModal = false;
                    confirmDeleteTag = null;
                "
            >
                Delete
            </flux:button>
        </div>
    </flux:modal>
</div>
