<div 
    wire:poll.5s.visible="refreshStashes"
    class="h-full flex flex-col bg-[#eff1f5] text-[#4c4f69] font-mono border-r border-[#ccd0da]"
    x-data="{ confirmDropIndex: null }"
>
    @if($error)
        <div class="bg-[#d20f39]/10 border-b border-[#d20f39]/30 text-[#d20f39] px-4 py-3 text-xs uppercase tracking-wider font-semibold">
            {{ $error }}
        </div>
    @endif

    <div class="px-4 py-4 border-b border-[#ccd0da]">
        <div class="flex items-center justify-between">
            <div class="text-xs uppercase tracking-wider font-medium text-[#9ca0b0]">Stashes</div>
            <flux:button 
                @click="$wire.showCreateModal = true"
                variant="primary" 
                size="sm"
                icon="archive-box"
                class="text-xs uppercase tracking-wider"
            >
                Stash
            </flux:button>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto">
        @if(empty($stashes))
            <div class="flex flex-col items-center justify-center h-full text-[#9ca0b0] space-y-4 px-4 animate-fade-in">
                <div class="w-16 h-16 mx-auto opacity-60">{!! file_get_contents(resource_path('svg/empty-states/no-changes.svg')) !!}</div>
                <div class="text-xs uppercase tracking-wider font-medium">No stashes</div>
            </div>
        @else
            <div class="divide-y divide-[#ccd0da]">
                @foreach($stashes as $stash)
                    <div class="group px-4 py-3 hover:bg-[#dce0e8] transition-colors">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0 space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <flux:badge variant="solid" color="zinc" class="font-mono text-xs">
                                        stash@{{{ $stash['index'] }}}
                                    </flux:badge>
                                    @if(!empty($stash['branch']))
                                        <flux:badge variant="subtle" color="blue" class="font-mono text-xs">
                                            {{ $stash['branch'] }}
                                        </flux:badge>
                                    @endif
                                </div>
                                <div class="text-sm text-[#6c6f85] break-words">
                                    {{ $stash['message'] }}
                                </div>
                                @if(!empty($stash['sha']))
                                    <div class="text-xs text-[#9ca0b0] font-mono">
                                        {{ substr($stash['sha'], 0, 7) }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <flux:tooltip content="Apply stash (keep in list)">
                                    <flux:button 
                                        wire:click="applyStash({{ $stash['index'] }})"
                                        icon="arrow-down-tray"
                                        variant="ghost" 
                                        size="sm"
                                        square
                                    />
                                </flux:tooltip>
                                <flux:tooltip content="Pop stash (apply and remove)">
                                    <flux:button 
                                        wire:click="popStash({{ $stash['index'] }})"
                                        icon="arrow-down-circle"
                                        variant="ghost" 
                                        size="sm"
                                        square
                                    />
                                </flux:tooltip>
                                <flux:tooltip content="Drop stash (delete)">
                                    <flux:button 
                                        @click="confirmDropIndex = {{ $stash['index'] }}"
                                        icon="trash"
                                        variant="ghost" 
                                        size="sm"
                                        square
                                    />
                                </flux:tooltip>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Create Stash Modal -->
    <flux:modal wire:model="showCreateModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Create Stash</flux:heading>
            <flux:subheading class="font-mono">
                Save your current changes to the stash
            </flux:subheading>
        </div>

        <flux:input 
            wire:model="stashMessage"
            label="Stash message"
            placeholder="Work in progress"
            class="font-mono"
        />

        <flux:checkbox 
            wire:model="includeUntracked"
            label="Include untracked files"
        />

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">Cancel</flux:button>
            <flux:button 
                variant="primary" 
                wire:click="createStash"
                :disabled="empty(trim($stashMessage))"
                class="uppercase tracking-wider"
            >
                Create Stash
            </flux:button>
        </div>
    </flux:modal>

    <!-- Drop Confirmation Modal -->
    <flux:modal x-model="confirmDropIndex !== null" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider text-[#d20f39]">Drop Stash</flux:heading>
            <flux:subheading class="font-mono">
                Are you sure? This action cannot be undone.
            </flux:subheading>
        </div>

        <div class="bg-[#e6e9ef] border border-[#ccd0da] rounded px-4 py-3">
            <div class="text-sm text-[#9ca0b0] font-mono">
                This will permanently delete <span class="text-[#4c4f69] font-semibold">stash@{<span x-text="confirmDropIndex"></span>}</span>
            </div>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" @click="confirmDropIndex = null">Cancel</flux:button>
            <flux:button 
                variant="danger" 
                @click="$wire.dropStash(confirmDropIndex); confirmDropIndex = null"
                class="uppercase tracking-wider"
            >
                Drop Stash
            </flux:button>
        </div>
    </flux:modal>
</div>
